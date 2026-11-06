try { if (localStorage.getItem('g360-menu') === 'recolhido') document.documentElement.classList.add('menu-recolhido'); } catch (e) {}
try { document.documentElement.dataset.theme=localStorage.getItem('g360-theme') === 'dark' ? 'dark' : 'light'; } catch (_) {}
