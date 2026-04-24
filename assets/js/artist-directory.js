(function () {
	const forms = document.querySelectorAll('.artist-directory__filter-form');

	forms.forEach((form) => {
		form.addEventListener('change', (event) => {
			if (!event.target.matches('input[name="media[]"]')) {
				return;
			}

			form.submit();
		});
	});
})();
