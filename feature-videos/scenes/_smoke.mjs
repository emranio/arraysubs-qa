export default {
  slug: '_smoke',
  tier: 'Free',
  intro: { title: 'Smoke *test*', sub: 'Checking the recording harness.', hold: 1.2 },
  outro: { title: 'Done', sub: 'Harness works.', url: 'subscriptionplugin.pro', hold: 1 },
  chapters: [
    {
      kicker: 'Admin',
      title: 'Subscriptions list',
      run: async (v) => {
        await v.as(1);
        await v.goto('/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions');
        await v.say('Every subscription in one list, with **status** and next payment.');
        await v.focus('.arraysubs-data-list, table', { label: 'Your subscriptions', dim: true });
        await v.unfocus();
        await v.zoom({ x: 160, y: 60, w: 600, h: 300 }, { hold: 1 });
        await v.zoomOut();
      },
    },
    {
      kicker: 'Storefront',
      title: 'Shop *page*',
      run: async (v) => {
        await v.as('guest');
        await v.goto('/shop/');
        await v.say('The storefront.');
        await v.scrollBy(500);
        await v.hold(0.8);
      },
    },
  ],
};
