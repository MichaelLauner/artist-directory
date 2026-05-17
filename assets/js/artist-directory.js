(function () {
	const forms = document.querySelectorAll('.artist-directory__filter-form');
	const menus = document.querySelectorAll('.artist-directory__filter-menu');

	forms.forEach((form) => {
		form.addEventListener('change', (event) => {
			if (!event.target.matches('input[name="media[]"]')) {
				return;
			}

			form.submit();
		});
	});

	document.addEventListener('click', (event) => {
		menus.forEach((menu) => {
			if (menu.contains(event.target)) {
				return;
			}

			menu.removeAttribute('open');
		});
	});

	document.addEventListener('keydown', (event) => {
		if (event.key !== 'Escape') {
			return;
		}

		menus.forEach((menu) => {
			menu.removeAttribute('open');
		});
	});
})();
