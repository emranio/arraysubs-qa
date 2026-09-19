(async () => {
  const env = window.arraySubs?.env;
  if (!env?.apiBaseUrl || !env?.nonce) throw new Error('Run from the logged-in ArraySubs admin page.');
  const read = async (path) => {
    const url = new URL(env.apiBaseUrl + 'arraysubs/v1/myaccount-editor/' + path, location.origin);
    if (url.origin !== location.origin) throw new Error('Unexpected API origin.');
    const response = await fetch(url, { headers: { 'X-WP-Nonce': env.nonce }, cache: 'no-store' });
    if (!response.ok) throw new Error(path + ': HTTP ' + response.status);
    return (await response.json()).content;
  };
  const [config, defaults] = await Promise.all([read('config'), read('defaults')]);
  const summarize = (item) => ({ id: item.id, label: item.label, enabled: item.enabled, type: item.type });
  const result = {
    core_version: env.version,
    customization_enabled: config.menu_items_enabled,
    saved_subscriptions: (config.items || []).filter(item => item.id === 'subscriptions').map(summarize),
    default_subscriptions: defaults.filter(item => item.id === 'subscriptions').map(summarize),
    saved_items: (config.items || []).map(summarize),
    default_items: defaults.map(summarize),
    visible_builder_labels: Array.from(document.querySelectorAll('.arraysubs-mae-item__label')).map(el => el.textContent.trim())
  };
  console.log(JSON.stringify(result, null, 2));
  return result;
})();
