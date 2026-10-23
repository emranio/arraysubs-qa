<?php

/**
 * Elementor Integration hooks.
 *
 * Wires the "ArraySubs Content Restrictions" controls onto Elementor Container
 * widgets (Flexbox + Grid layouts share the `container` element) and applies the
 * gating at render time by delegating to the already-registered
 * [arraysubs_restrict] / [arraysubs_visibility] shortcode callbacks. No
 * restriction logic is reimplemented here.
 *
 * @package ArraySubs\Features\ElementorIntegration\Services
 */

namespace ArraySubs\Features\ElementorIntegration\Services;

defined('ABSPATH') || exit;

use ArraySubs\Supports\Config;
use ArraySubs\Features\ElementorIntegration\Controls\AjaxSelect;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

/**
 * Class Hooks
 */
class OriginalHooks
{
    /**
     * Auto-load flag for AbstractLoader.
     *
     * @var bool
     */
    public static $loadable = true;

    /**
     * LIFO stack of open restriction buffers. Each frame is
     * ['oid' => spl_object_id($element), 'level' => ob_get_level()].
     *
     * Using the element's live object id (not its saved get_id() string) keeps
     * start/stop balanced and collision-proof even when duplicate saved ids are
     * rendered in one pass (self-embedding/global/loop templates). The buffer
     * level lets the shutdown safety net fail closed on an aborted inner render.
     *
     * @var array<int,array{oid:int,level:int}>
     */
    private $bufferStack = [];

    /**
     * Whether the fail-closed shutdown safety net has been registered.
     *
     * @var bool
     */
    private $shutdownHooked = false;

    /**
     * Element ids that already received our controls (guards double registration
     * when both Attributes-section hooks resolve).
     *
     * @var array<string,bool>
     */
    private $registered = [];

    /**
     * Hooks constructor.
     */
    public function __construct()
    {
        // Register the custom AJAX select control type.
        add_action('elementor/controls/register', [$this, 'registerControlTypes']);

        // Inject our section right after the native "Attributes" section in the
        // Advanced tab of the Container element (covers Flexbox + Grid layouts).
        // Free Elementor exposes the Attributes section as `section_custom_attributes_pro`
        // (an upgrade teaser); Elementor Pro replaces it with `_section_attributes`.
        // Hooking both lands our section after Attributes regardless of which is active.
        add_action(
            'elementor/element/container/section_custom_attributes_pro/after_section_end',
            [$this, 'registerControls'],
            10,
            2
        );
        add_action(
            'elementor/element/container/_section_attributes/after_section_end',
            [$this, 'registerControls'],
            10,
            2
        );

        // Editor-side assets (custom control view + REST config).
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueueEditorAssets']);

        // Wrap container output with the restriction shortcode at render time.
        add_action('elementor/frontend/before_render', [$this, 'beforeRender']);
        add_action('elementor/frontend/after_render', [$this, 'afterRender']);
    }

    /**
     * Register the custom control type with Elementor.
     *
     * @param Controls_Manager $controls_manager Elementor controls manager.
     * @return void
     */
    public function registerControlTypes($controls_manager): void
    {
        if (!class_exists('\Elementor\Control_Select2')) {
            return;
        }

        $controls_manager->register(new AjaxSelect());
    }

    /**
     * Register the "ArraySubs Content Restrictions" controls.
     *
     * @param Controls_Stack $element Element the controls are added to.
     * @param array          $args    Section args (unused).
     * @return void
     */
    public function registerControls($element, $args = []): void
    {
        if ($element instanceof \Elementor\Element_Base) {
            $id = $element->get_id();
            if (!empty($this->registered[$id])) {
                return;
            }
            $this->registered[$id] = true;
        }

        $element->start_controls_section(
            'arraysubs_content_restrictions',
            [
                'label' => esc_html__('ArraySubs Content Restrictions', 'arraysubs'),
                'tab'   => Controls_Manager::TAB_ADVANCED,
            ]
        );

        $element->add_control(
            'arraysubs_enable',
            [
                'label'        => esc_html__('Enable Restriction', 'arraysubs'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'arraysubs'),
                'label_off'    => esc_html__('No', 'arraysubs'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $element->add_control(
            'arraysubs_mode',
            [
                'label'     => esc_html__('Restriction Type', 'arraysubs'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'restrict',
                'options'   => [
                    'restrict'   => esc_html__('Subscription / Role / Purchase (restrict)', 'arraysubs'),
                    'visibility' => esc_html__('Login state (visibility)', 'arraysubs'),
                ],
                'condition' => ['arraysubs_enable' => 'yes'],
            ]
        );

        $this->addVisibilityControls($element);
        $this->addRestrictControls($element);

        $element->end_controls_section();
    }

    /**
     * Controls for the [arraysubs_visibility] mode.
     *
     * @param Controls_Stack $element Element.
     * @return void
     */
    private function addVisibilityControls($element): void
    {
        $element->add_control(
            'arraysubs_vis_show',
            [
                'label'     => esc_html__('Show To', 'arraysubs'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'logged_in',
                'options'   => [
                    'logged_in'  => esc_html__('Logged-in users', 'arraysubs'),
                    'logged_out' => esc_html__('Logged-out users', 'arraysubs'),
                ],
                'condition' => [
                    'arraysubs_enable' => 'yes',
                    'arraysubs_mode'   => 'visibility',
                ],
            ]
        );

        $element->add_control(
            'arraysubs_vis_fallback',
            [
                'label'       => esc_html__('Fallback Content', 'arraysubs'),
                'description' => esc_html__('Shown to everyone else. Plain text or a shortcode.', 'arraysubs'),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => '',
                'condition'   => [
                    'arraysubs_enable' => 'yes',
                    'arraysubs_mode'   => 'visibility',
                ],
            ]
        );
    }

    /**
     * Controls for the [arraysubs_restrict] mode (all shortcode attributes).
     *
     * @param Controls_Stack $element Element.
     * @return void
     */
    private function addRestrictControls($element): void
    {
        $restrict_condition = [
            'arraysubs_enable' => 'yes',
            'arraysubs_mode'   => 'restrict',
        ];

        $element->add_control(
            'arraysubs_status',
            [
                'label'       => esc_html__('Subscription Status', 'arraysubs'),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => true,
                'label_block' => true,
                'options'     => $this->getStatusOptions(),
                'condition'   => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_products',
            [
                'label'           => esc_html__('Active Subscription To Products', 'arraysubs'),
                'type'            => AjaxSelect::CONTROL_TYPE,
                'multiple'        => true,
                'label_block'     => true,
                'arraysubs_select' => [
                    'type'  => 'products',
                    // Subscription filter carried on a dedicated param so it does
                    // not collide with the dispatcher's `type` (route) param.
                    'query' => ['product_type' => 'subscription'],
                ],
                'condition'       => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_variations',
            [
                'label'           => esc_html__('Active Subscription To Variations', 'arraysubs'),
                'type'            => AjaxSelect::CONTROL_TYPE,
                'multiple'        => true,
                'label_block'     => true,
                // Subscription filter rides on the non-colliding `product_type`
                // param (a top-level `type` would clobber the dispatcher route).
                'arraysubs_select' => [
                    'type'  => 'variations',
                    'query' => ['product_type' => 'subscription'],
                ],
                'condition'       => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_purchased',
            [
                'label'           => esc_html__('Purchased Products (any purchase)', 'arraysubs'),
                'type'            => AjaxSelect::CONTROL_TYPE,
                'multiple'        => true,
                'label_block'     => true,
                'arraysubs_select' => [
                    'type' => 'products',
                ],
                'condition'       => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_roles',
            [
                'label'       => esc_html__('User Roles', 'arraysubs'),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => true,
                'label_block' => true,
                'options'     => $this->getRoleOptions(),
                'condition'   => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_lifetime_spent',
            [
                'label'       => esc_html__('Minimum Lifetime Spend', 'arraysubs'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'step'        => 0.01,
                'default'     => '',
                'condition'   => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_condition',
            [
                'label'     => esc_html__('Match Logic', 'arraysubs'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'and',
                'options'   => [
                    'and' => esc_html__('Match ALL of the above (AND)', 'arraysubs'),
                    'or'  => esc_html__('Match ANY of the above (OR)', 'arraysubs'),
                ],
                'condition' => $restrict_condition,
            ]
        );

        // --- Feature Manager (Pro) gating ---
        $feature_set = array_merge($restrict_condition, ['arraysubs_feature!' => '']);

        $element->add_control(
            'arraysubs_feature_heading',
            [
                'label'     => esc_html__('Plan Feature (Pro)', 'arraysubs'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_feature_note',
            [
                'type'      => Controls_Manager::RAW_HTML,
                'raw'       => esc_html__('Gate by a feature defined on your subscription products (Product → Features, requires ArraySubs Pro). Pick a feature, then choose how to check it. Leave empty to not use a feature rule.', 'arraysubs'),
                'content_classes' => 'elementor-control-field-description',
                'condition' => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_feature',
            [
                'label'           => esc_html__('Feature', 'arraysubs'),
                'type'            => AjaxSelect::CONTROL_TYPE,
                'multiple'        => false,
                'label_block'     => true,
                'description'     => esc_html__('The feature name, exactly as defined on the product.', 'arraysubs'),
                'arraysubs_select' => [
                    'type' => 'features',
                ],
                'condition'       => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_feature_match',
            [
                'label'       => esc_html__('How To Check It', 'arraysubs'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'has',
                'options'     => [
                    'has'        => esc_html__('Has the feature (on/off features)', 'arraysubs'),
                    'min_total'  => esc_html__('Combined amount across their plans is at least…', 'arraysubs'),
                    'min_single' => esc_html__('At least one plan provides at least…', 'arraysubs'),
                    'custom'     => esc_html__('Custom comparison (advanced)', 'arraysubs'),
                ],
                'description' => esc_html__('On/off (toggle) features: use "Has the feature". Numeric features (e.g. seats, API calls): use one of the "at least" options.', 'arraysubs'),
                'condition'   => $feature_set,
            ]
        );

        $element->add_control(
            'arraysubs_feature_value',
            [
                'label'       => esc_html__('Required Amount', 'arraysubs'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'default'     => '',
                'description' => esc_html__('The number the feature must reach.', 'arraysubs'),
                'condition'   => array_merge($feature_set, [
                    'arraysubs_feature_match' => ['min_total', 'min_single', 'custom'],
                ]),
            ]
        );

        $element->add_control(
            'arraysubs_feature_op',
            [
                'label'     => esc_html__('Comparison', 'arraysubs'),
                'type'      => Controls_Manager::SELECT,
                'default'   => '>=',
                'options'   => [
                    '>=' => esc_html__('At least (>=)', 'arraysubs'),
                    '>'  => esc_html__('More than (>)', 'arraysubs'),
                    '='  => esc_html__('Exactly (=)', 'arraysubs'),
                    '!=' => esc_html__('Not equal (!=)', 'arraysubs'),
                    '<'  => esc_html__('Less than (<)', 'arraysubs'),
                    '<=' => esc_html__('At most (<=)', 'arraysubs'),
                ],
                'condition' => array_merge($feature_set, ['arraysubs_feature_match' => 'custom']),
            ]
        );

        $element->add_control(
            'arraysubs_aggregation',
            [
                'label'       => esc_html__('Combine Multiple Plans By', 'arraysubs'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'sum',
                'description' => esc_html__('For presence (on/off) checks use the "Has the feature" mode instead.', 'arraysubs'),
                'options'     => [
                    'sum' => esc_html__('Sum — add values across plans', 'arraysubs'),
                    'max' => esc_html__('Max — highest single plan', 'arraysubs'),
                ],
                'condition'   => array_merge($feature_set, ['arraysubs_feature_match' => 'custom']),
            ]
        );

        // --- Denial behaviour ---
        $element->add_control(
            'arraysubs_behaviour_heading',
            [
                'label'     => esc_html__('When Access Is Denied', 'arraysubs'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => $restrict_condition,
            ]
        );

        $element->add_control(
            'arraysubs_message',
            [
                'label'       => esc_html__('Restricted Message', 'arraysubs'),
                'description' => $this->getMessageDescription(),
                'type'        => Controls_Manager::WYSIWYG,
                'default'     => '',
                'condition'   => $restrict_condition,
            ]
        );

        // Note: the shortcode's `redirect` attribute is intentionally not exposed.
        // It performs wp_safe_redirect()+exit on denial, which cannot run safely
        // at container render time (output has already started inside the_content)
        // and would redirect the whole page — defeating partial, inline gating.

        $element->add_control(
            'arraysubs_login_required',
            [
                'label'        => esc_html__('Only login is required—nothing else', 'arraysubs'),
                'description'  => esc_html__('Any logged-in user can view this content. All other conditions are skipped; guests are denied.', 'arraysubs'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'arraysubs'),
                'label_off'    => esc_html__('No', 'arraysubs'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => ['arraysubs_enable' => 'yes'],
            ]
        );

        $element->add_control(
            'arraysubs_show_to_admins',
            [
                'label'        => esc_html__('Always Show To Admins', 'arraysubs'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'arraysubs'),
                'label_off'    => esc_html__('No', 'arraysubs'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => ['arraysubs_enable' => 'yes'],
            ]
        );
    }

    /**
     * Restricted Message help, linking to the default message it falls back to.
     *
     * Opens in a new tab so the Elementor editor keeps unsaved changes.
     *
     * @return string Escaped HTML.
     */
    private function getMessageDescription(): string
    {
        $link = sprintf(
            '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s<span aria-hidden="true">&nbsp;&#8599;</span><span class="elementor-screen-only"> %3$s</span></a>',
            esc_url(arraysubs_get_content_gate_admin_url('default-message')),
            esc_html__('ArraySubs → Settings → Content Gate', 'arraysubs'),
            esc_html__('(opens in a new tab)', 'arraysubs')
        );

        return sprintf(
            /* translators: %s: "ArraySubs → Settings → Content Gate" link. */
            esc_html__('Shown in place of the content. Leave empty to use the default message from %s.', 'arraysubs'),
            $link
        );
    }

    /**
     * Start buffering a container's output when restriction is enabled.
     *
     * @param mixed $element Elementor element being rendered.
     * @return void
     */
    public function beforeRender($element): void
    {
        if (!$this->isTargetElement($element) || $this->isBuilderContext()) {
            return;
        }

        $settings = $element->get_settings_for_display();
        if (($settings['arraysubs_enable'] ?? '') !== 'yes') {
            return;
        }

        $this->ensureShutdownGuard();
        $this->bufferStack[] = ['oid' => spl_object_id($element), 'level' => ob_get_level()];
        ob_start();
    }

    /**
     * Apply the restriction shortcode to a buffered container's output.
     *
     * @param mixed $element Elementor element being rendered.
     * @return void
     */
    public function afterRender($element): void
    {
        if (!($element instanceof \Elementor\Element_Base) || empty($this->bufferStack)) {
            return;
        }

        // Only close the buffer for the exact element instance that opened it.
        // Matching by live object id (not the saved get_id() string) is immune to
        // duplicate saved ids rendered in one pass.
        $top = end($this->bufferStack);
        if ($top['oid'] !== spl_object_id($element)) {
            return;
        }

        array_pop($this->bufferStack);

        $html = ob_get_clean();

        echo $this->applyRestriction($element->get_settings_for_display(), (string) $html); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Register a one-time shutdown safety net that discards any restriction
     * buffer left open by an aborted inner render (exit/wp_die/wp_redirect inside
     * a gated container). Discarding — rather than flushing — keeps the gate
     * fail-closed: restricted content is never emitted ungated.
     *
     * @return void
     */
    private function ensureShutdownGuard(): void
    {
        if ($this->shutdownHooked) {
            return;
        }

        $this->shutdownHooked = true;

        add_action('shutdown', function () {
            // Drain LIFO: discard any output buffered above each unclosed frame.
            while (!empty($this->bufferStack)) {
                $frame = array_pop($this->bufferStack);
                while (ob_get_level() > $frame['level']) {
                    ob_end_clean();
                }
            }
        }, 0);
    }

    /**
     * Build shortcode attributes from settings and delegate to the registered
     * shortcode callback (no restriction logic is duplicated here).
     *
     * @param array  $s    Resolved element settings.
     * @param string $html Rendered inner HTML.
     * @return string
     */
    private function applyRestriction(array $s, string $html): string
    {
        $mode = $s['arraysubs_mode'] ?? 'restrict';

        // Overrides make the decision before any visibility or condition evaluation.
        if (($s['arraysubs_show_to_admins'] ?? 'yes') === 'yes' && current_user_can('manage_options')) {
            return $html;
        }
        if ($mode === 'visibility' && ($s['arraysubs_login_required'] ?? '') === 'yes') {
            return $this->callShortcode('arraysubs_restrict', [
                'login_required' => 'true',
                'show_to_admins' => 'false',
                'message'        => wp_kses_post((string) ($s['arraysubs_vis_fallback'] ?? '')),
                'prerendered'    => 'true',
            ], $html);
        }

        if ($mode === 'visibility') {
            $show = $s['arraysubs_vis_show'] ?? 'logged_in';

            return $this->callShortcode('arraysubs_visibility', [
                'show'        => in_array($show, ['logged_in', 'logged_out'], true) ? $show : 'logged_in',
                // Defence-in-depth: editors with unfiltered_html bypass Elementor's
                // save-time kses, so sanitize the fallback before it is echoed.
                'fallback'    => wp_kses_post((string) ($s['arraysubs_vis_fallback'] ?? '')),
                // The buffered HTML is already shortcode-expanded by Elementor.
                'prerendered'    => 'true',
            ], $html);
        }

        $feature = $this->featureAttributes($s);

        $status     = $this->toCsv($s['arraysubs_status'] ?? '');
        $products   = $this->toCsv($s['arraysubs_products'] ?? '');
        $variations = $this->toCsv($s['arraysubs_variations'] ?? '');
        $purchased  = $this->toCsv($s['arraysubs_purchased'] ?? '');
        $lifetime   = (string) ($s['arraysubs_lifetime_spent'] ?? '');
        $roles      = $this->toCsv($s['arraysubs_roles'] ?? '');

        $atts = [
            'status'         => $status,
            'products'       => $products,
            'variations'     => $variations,
            'purchased'      => $purchased,
            'lifetime_spent' => $lifetime,
            'feature'        => $feature['feature'],
            'feature_min'    => $feature['feature_min'],
            'feature_op'     => $feature['feature_op'],
            'aggregation'    => $feature['aggregation'],
            'roles'          => $roles,
            'condition'      => arraysubs_normalize_condition_logic($s['arraysubs_condition'] ?? null),
            'message'        => (string) ($s['arraysubs_message'] ?? ''),
            // Force empty: redirect-on-deny is unsafe at render time (see addRestrictControls()).
            'redirect'       => '',
            'login_required' => (($s['arraysubs_login_required'] ?? '') === 'yes') ? 'true' : 'false',
            'show_to_admins' => (($s['arraysubs_show_to_admins'] ?? 'yes') === 'yes') ? 'true' : 'false',
            // The buffered HTML is already shortcode-expanded by Elementor.
            'prerendered'    => 'true',
        ];

        return $this->callShortcode('arraysubs_restrict', $atts, $html);
    }

    /**
     * Translate the guided "How to check it" mode into the shortcode's raw
     * feature attributes (feature_op / aggregation / feature_min).
     *
     * - has        -> presence test: aggregation=any, op>=, min 1 (works for toggle features)
     * - min_total  -> aggregation=sum, op>=, min=amount  (numeric: total across plans)
     * - min_single -> aggregation=max, op>=, min=amount  (numeric: best single plan)
     * - custom     -> user-chosen operator + aggregation + amount
     *
     * @param array $s Resolved element settings.
     * @return array{feature:string,feature_min:string,feature_op:string,aggregation:string}
     */
    private function featureAttributes(array $s): array
    {
        $feature = trim((string) ($s['arraysubs_feature'] ?? ''));

        if ($feature === '') {
            return ['feature' => '', 'feature_min' => '', 'feature_op' => '>=', 'aggregation' => 'sum'];
        }

        $match  = $s['arraysubs_feature_match'] ?? 'has';
        $amount = (string) ($s['arraysubs_feature_value'] ?? '');

        switch ($match) {
            case 'min_total':
                return ['feature' => $feature, 'feature_min' => ($amount !== '' ? $amount : '1'), 'feature_op' => '>=', 'aggregation' => 'sum'];

            case 'min_single':
                return ['feature' => $feature, 'feature_min' => ($amount !== '' ? $amount : '1'), 'feature_op' => '>=', 'aggregation' => 'max'];

            case 'custom':
                return [
                    'feature'     => $feature,
                    'feature_min' => $amount,
                    'feature_op'  => (string) ($s['arraysubs_feature_op'] ?? '>='),
                    'aggregation' => (string) ($s['arraysubs_aggregation'] ?? 'sum'),
                ];

            case 'has':
            default:
                return ['feature' => $feature, 'feature_min' => '1', 'feature_op' => '>=', 'aggregation' => 'any'];
        }
    }

    /**
     * Invoke a registered shortcode callback directly with an attributes array.
     *
     * Reuses the live handler instance registered by the plugin's own shortcode
     * services, avoiding both re-instantiation side effects and shortcode-string
     * escaping. Falls back to the raw content if the shortcode is not registered
     * (e.g. Members Access feature disabled).
     *
     * @param string $tag     Shortcode tag.
     * @param array  $atts    Attributes.
     * @param string $content Inner content.
     * @return string
     */
    private function callShortcode(string $tag, array $atts, string $content): string
    {
        global $shortcode_tags;

        if (isset($shortcode_tags[$tag]) && is_callable($shortcode_tags[$tag])) {
            return (string) call_user_func($shortcode_tags[$tag], $atts, $content, $tag);
        }

        return $content;
    }

    /**
     * Enqueue the editor-side custom control view and its REST config.
     *
     * @return void
     */
    public function enqueueEditorAssets(): void
    {
        $handle = 'arraysubs-elementor-editor';

        wp_enqueue_script(
            $handle,
            Config::get('plugin.url') . 'src/Features/ElementorIntegration/assets/editor.js',
            ['jquery', 'elementor-editor', 'underscore', 'wp-api-fetch'],
            Config::get('plugin.version'),
            true
        );

        wp_localize_script($handle, 'ArraySubsElementor', [
            // Full URL for the jQuery fallback; `path` is used by wp.apiFetch,
            // whose nonce middleware survives nonce rotation in long sessions.
            'restUrl' => esc_url_raw(rest_url('arraysubs/v1/members-access/select-options')),
            'path'    => '/arraysubs/v1/members-access/select-options',
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Whether the element is a Container (Flexbox or Grid layout).
     *
     * @param mixed $element Elementor element.
     * @return bool
     */
    private function isTargetElement($element): bool
    {
        return ($element instanceof \Elementor\Element_Base)
            && $element->get_name() === 'container';
    }

    /**
     * Whether we are in the Elementor editor or its preview (never gate there).
     *
     * @return bool
     */
    private function isBuilderContext(): bool
    {
        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }

        $plugin = \Elementor\Plugin::$instance;

        return ($plugin->editor && $plugin->editor->is_edit_mode())
            || ($plugin->preview && $plugin->preview->is_preview_mode());
    }

    /**
     * Normalise a control value (array or scalar) to a comma-separated string.
     *
     * @param mixed $value Control value.
     * @return string
     */
    private function toCsv($value): string
    {
        if (is_array($value)) {
            $value = array_filter(array_map('strval', $value), static function ($v) {
                return $v !== '';
            });

            return implode(',', $value);
        }

        return (string) $value;
    }

    /**
     * Subscription status options (short slugs the shortcode expects).
     *
     * @return array<string,string>
     */
    private function getStatusOptions(): array
    {
        $options = [];

        if (function_exists('arraysubs_get_subscription_statuses')) {
            foreach (arraysubs_get_subscription_statuses() as $slug => $label) {
                $short = preg_replace('/^arraysubs-/', '', $slug);
                $options[$short] = $label;
            }
        }

        return $options;
    }

    /**
     * WordPress role options.
     *
     * @return array<string,string>
     */
    private function getRoleOptions(): array
    {
        $options = [];

        if (function_exists('wp_roles')) {
            foreach (wp_roles()->get_names() as $slug => $label) {
                $options[$slug] = $label;
            }
        }

        return $options;
    }
}
