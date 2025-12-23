document.addEventListener('DOMContentLoaded', () => {

  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
      nav.classList.toggle('open');
    });
  }
});
const themeToggle = document.querySelector('[data-theme-toggle]');
if (themeToggle) {
  themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('theme-alt');
    if (document.body.classList.contains('theme-alt')) {
      localStorage.setItem('theme', 'dark');
    } else {
      localStorage.setItem('theme', 'light');
    }
  });
}
if (localStorage.getItem('theme') === 'dark') {
  document.body.classList.add('theme-alt');
}

