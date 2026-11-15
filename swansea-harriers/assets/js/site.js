/**
 * Swansea Harriers: small, dependency-free behaviours.
 * Everything works without JavaScript (all content is in the HTML); this only
 * adds tabs, filters, round switching, the mobile menu and "copy link".
 */
(function () {
	'use strict';

	const $$ = (sel, root) => Array.prototype.slice.call((root || document).querySelectorAll(sel));

	/* Mobile menu */
	$$('.hs-nav__toggle').forEach((btn) => {
		btn.addEventListener('click', () => {
			const nav = btn.closest('.hs-nav');
			const open = nav.classList.toggle('is-open');
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	});

	/* Tabs: any [role=tablist] whose buttons control [role=tabpanel] by id */
	$$('[role="tablist"]').forEach((list) => {
		const tabs = $$('[role="tab"]', list);
		const select = (tab) => {
			tabs.forEach((t) => {
				const on = t === tab;
				t.setAttribute('aria-selected', on ? 'true' : 'false');
				t.tabIndex = on ? 0 : -1;
				const panel = document.getElementById(t.getAttribute('aria-controls'));
				if (panel) panel.hidden = !on;
			});
		};
		tabs.forEach((t, i) => {
			t.tabIndex = t.getAttribute('aria-selected') === 'true' ? 0 : -1;
			t.addEventListener('click', () => select(t));
			t.addEventListener('keydown', (e) => {
				if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
				const next = tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
				select(next);
				next.focus();
			});
		});
		// Open a tab from the URL hash, e.g. /results/#tab-summer
		const fromHash = location.hash && tabs.find((t) => '#' + t.id === location.hash);
		if (fromHash) select(fromHash);
	});

	/* Results: round buttons inside each league */
	$$('[data-league]').forEach((league) => {
		const buttons = $$('.hs-round-btn', league);
		buttons.forEach((b) => {
			b.addEventListener('click', () => {
				buttons.forEach((o) => {
					const on = o === b;
					o.setAttribute('aria-pressed', on ? 'true' : 'false');
					const p = document.getElementById(o.getAttribute('aria-controls'));
					if (p) p.hidden = !on;
				});
			});
		});
	});

	/* Chip groups: one pressed at a time */
	const pressOnly = (chips, chosen) => chips.forEach((c) => c.setAttribute('aria-pressed', c === chosen ? 'true' : 'false'));

	/* Fixtures filter */
	$$('[data-fixtures]').forEach((root) => {
		const chips = $$('[data-filter]', root);
		const empty = root.querySelector('[data-fixtures-empty]');
		chips.forEach((chip) => {
			chip.addEventListener('click', () => {
				pressOnly(chips, chip);
				const f = chip.dataset.filter;
				let shown = 0;
				$$('[data-month]', root).forEach((month) => {
					let any = false;
					$$('.hs-fixture', month).forEach((row) => {
						const ok = f === 'all' || (f === 'league' ? row.dataset.type === 'league' : row.dataset.discipline === f);
						row.hidden = !ok;
						if (ok) { any = true; shown++; }
					});
					month.hidden = !any;
				});
				if (empty) empty.hidden = shown > 0;
			});
		});
	});

	/* Volunteers: coach filter by event group */
	$$('[data-people]').forEach((root) => {
		const chips = $$('[data-group]', root).filter((el) => el.classList.contains('hs-chip'));
		chips.forEach((chip) => {
			chip.addEventListener('click', () => {
				pressOnly(chips, chip);
				const g = chip.dataset.group;
				$$('.hs-person', root).forEach((p) => {
					p.hidden = !(g === 'all' || (' ' + p.dataset.groups + ' ').indexOf(' ' + g + ' ') > -1);
				});
			});
		});
	});

	/* Records: event group chips + age dropdown */
	$$('[data-records]').forEach((root) => {
		const chips = $$('.hs-chip', root);
		const age = root.querySelector('#hs-age');
		const apply = () => {
			const on = chips.find((c) => c.getAttribute('aria-pressed') === 'true');
			const g = on ? on.dataset.group : '';
			const a = age ? age.value : '';
			$$('tbody tr', root).forEach((tr) => {
				const okG = !g || (' ' + tr.dataset.group + ' ').indexOf(' ' + g + ' ') > -1;
				const okA = !a || tr.dataset.age === a;
				tr.hidden = !(okG && okA);
			});
		};
		chips.forEach((c) => c.addEventListener('click', () => { pressOnly(chips, c); apply(); }));
		if (age) age.addEventListener('change', apply);
		apply();
	});

	/* Copy link */
	$$('[data-copy]').forEach((btn) => {
		btn.addEventListener('click', () => {
			const done = () => { const t = btn.textContent; btn.textContent = 'Link copied'; setTimeout(() => { btn.textContent = t; }, 2000); };
			if (navigator.clipboard) navigator.clipboard.writeText(btn.dataset.copy).then(done).catch(() => {});
		});
	});
})();
