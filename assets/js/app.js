jQuery(document).ready(function ($) {

	// Navigation
	$('.fk-nav-item, .fk-mobile-nav-item[data-view]').on('click', function (e) {
		e.preventDefault();
		$('.fk-nav-item, .fk-mobile-nav-item').removeClass('active');

		var view = $(this).data('view');

		// Active state sync
		$('.fk-nav-item[data-view="' + view + '"], .fk-mobile-nav-item[data-view="' + view + '"]').addClass('active');

		$('.fk-view-container').hide();
		$('#view-' + view).fadeIn();

		if (view === 'reports') {
			loadReports();
		}
	});

	// Mobile Quick Add
	$('#mobile-add-btn').on('click', function (e) {
		e.preventDefault();
		loadContactsDropdown();
		$('#modal-entry-overlay').css('display', 'flex');
	});

	// Modal handlers
	$('#btn-add-entry').on('click', function () {
		loadContactsDropdown();
		$('#modal-entry-overlay').css('display', 'flex');
	});

	$('#btn-add-contact').on('click', function () {
		$('#modal-contact-overlay').css('display', 'flex');
	});

	$('.fk-modal-close, .fk-modal-overlay').on('click', function (e) {
		if (e.target === this || $(this).hasClass('fk-modal-close')) {
			$(this).closest('.fk-modal-overlay').hide();
		}
	});

	// Quick Add Contact from Entry Modal
	$('#btn-quick-add-contact').on('click', function () {
		$('#modal-contact-overlay').css('display', 'flex');
	});

	// Entry Type Logic
	$('#entry_type').on('change', function () {
		if ($(this).val().startsWith('udhaar')) {
			$('#contact_id').prop('required', true);
			$('#contact-label').text('Name / Contact (Required)');
			$('#contact-label').css('color', 'var(--danger)');
		} else {
			$('#contact_id').prop('required', false);
			$('#contact-label').text('Name / Contact (Optional)');
			$('#contact-label').css('color', 'var(--text-main)');
		}
	});

	// Load Data
	function loadDashboardStats() {
		$.post(FaraniKhata.ajaxurl, {
			action: 'fk_get_stats',
			nonce: FaraniKhata.nonce
		}, function (response) {
			if (response.success) {
				$('#stat-cash-in-hand').text('Rs ' + response.data.cash_in_hand);
				$('#stat-udhaar-recover').text('Rs ' + response.data.udhaar_to_recover);
				$('#stat-payable').text('Rs ' + response.data.payable);
			}
		});
	}

	function loadRecentTransactions() {
		$.post(FaraniKhata.ajaxurl, {
			action: 'fk_get_transactions',
			nonce: FaraniKhata.nonce,
			limit: 5 // Load only 5 for dashboard
		}, function (response) {
			if (response.success) {
				renderTransactions('#tbl-recent-transactions', response.data);
			}
		});
	}

	function loadDailyTransactions(start = '', end = '') {
		$.post(FaraniKhata.ajaxurl, {
			action: 'fk_get_transactions',
			nonce: FaraniKhata.nonce,
			start_date: start,
			end_date: end
		}, function (response) {
			if (response.success) {
				renderTransactions('#tbl-daily-transactions', response.data);
			}
		});
	}

	function renderTransactions(selector, data) {
		var tableBody = $(selector);
		tableBody.empty();

		if (data.length === 0) {
			tableBody.append('<tr><td colspan="5" style="text-align:center;">No transactions found.</td></tr>');
			return;
		}

		$.each(data, function (index, item) {
			var badgeClass = 'badge-' + item.type;
			var typeLabel = item.type.charAt(0).toUpperCase() + item.type.slice(1);
			var amountPrefix = (item.type === 'expense' || (item.type === 'udhaar' && item.amount < 0)) ? '-' : '+';
			var amountColor = (item.type === 'expense' || (item.type === 'udhaar' && parseFloat(item.amount) > 0)) ? 'var(--danger)' : 'var(--success)';

			if (item.type === 'udhaar') {
				if (item.amount > 0) {
					amountPrefix = '-';
					typeLabel = 'Udhaar Given';
				} else {
					amountPrefix = '+';
					typeLabel = 'Udhaar Returned';
				}
			}

			var tr = $('<tr class="swipe-row" data-id="' + item.id + '">').append(
				$('<td>').text(item.date),
				$('<td>').text(item.description),
				$('<td>').html('<span class="badge ' + badgeClass + '">' + typeLabel + '</span>'),
				$('<td>').css('color', amountColor).text(amountPrefix + ' Rs ' + Math.abs(item.amount)),
				$('<td>').html(`
					<button class="fk-btn fk-btn-secondary btn-edit-tx" style="padding:4px 8px; font-size:12px;"><i class="fa-solid fa-pen"></i></button>
					<button class="fk-btn fk-btn-primary btn-delete-tx" style="background:var(--danger); padding:4px 8px; font-size:12px;"><i class="fa-solid fa-trash"></i></button>
				`)
			);
			tableBody.append(tr);
		});

		initSwipeLogic();
	}

	function initSwipeLogic() {
		// Basic Swipe-to-Reveal logic for touch devices
		let touchstartX = 0;
		let touchendX = 0;

		$('.swipe-row').on('touchstart', function (event) {
			touchstartX = event.changedTouches[0].screenX;
		});

		$('.swipe-row').on('touchend', function (event) {
			touchendX = event.changedTouches[0].screenX;
			handleSwipe($(this));
		});

		function handleSwipe(element) {
			if (touchendX < touchstartX - 50) {
				// Swipe Left - Reveal Actions
				$(element).css('transform', 'translateX(-80px)');
			}
			if (touchendX > touchstartX + 50) {
				// Swipe Right - Hide Actions
				$(element).css('transform', 'translateX(0)');
			}
		}
	}

	function loadContacts(search = '') {
		$.post(FaraniKhata.ajaxurl, {
			action: 'fk_get_contacts',
			nonce: FaraniKhata.nonce,
			search: search
		}, function (response) {
			if (response.success) {
				var tableBody = $('#tbl-contacts');
				tableBody.empty();

				if (response.data.length === 0) {
					tableBody.append('<tr><td colspan="4" style="text-align:center;">No contacts found.</td></tr>');
					return;
				}

				$.each(response.data, function (index, c) {
					var balColor = (parseFloat(c.total_balance) > 0) ? 'var(--danger)' : 'var(--text-main)';
					var tr = $('<tr>').append(
						$('<td>').text(c.name),
						$('<td>').text(c.phone),
						$('<td>').css('color', balColor).text('Rs ' + c.total_balance),
						$('<td>').html(`
							<a href="${c.wa_link}" target="_blank" class="fk-btn" style="background:#25D366; color:white; padding: 5px 10px; font-size:12px;"><i class="fa-brands fa-whatsapp"></i> Chat</a>
							<button class="fk-btn fk-btn-secondary fk-btn-sm" style="padding: 5px 10px; font-size:12px;"><i class="fa-solid fa-list"></i> Ledger</button>
						`)
					);
					tableBody.append(tr);
				});
			}
		});
	}

	function loadContactsDropdown() {
		$.post(FaraniKhata.ajaxurl, {
			action: 'fk_get_contacts',
			nonce: FaraniKhata.nonce
		}, function (response) {
			if (response.success) {
				var dropdown = $('#contact_id');
				dropdown.find('option:not(:first)').remove(); // Keep the default one
				$.each(response.data, function (index, c) {
					dropdown.append($('<option>').val(c.id).text(c.name));
				});
			}
		});
	}

	function loadReports() {
		$.post(FaraniKhata.ajaxurl, {
			action: 'fk_get_reports',
			nonce: FaraniKhata.nonce
		}, function (response) {
			if (response.success) {
				var tableBody = $('#tbl-monthly-reports');
				tableBody.empty();

				$.each(response.data, function (index, month) {
					var tr = $('<tr>').append(
						$('<td>').text(month.month_year),
						$('<td>').css('color', 'var(--success)').text('Rs ' + month.income),
						$('<td>').css('color', 'var(--danger)').text('Rs ' + month.expense),
						$('<td>').css('color', month.net >= 0 ? 'var(--success)' : 'var(--danger)').text('Rs ' + month.net)
					);
					tableBody.append(tr);
				});
			}
		});
	}

	// Form Submissions
	$('#form-add-entry').on('submit', function (e) {
		e.preventDefault();
		var data = $(this).serialize() + '&action=fk_add_transaction&nonce=' + FaraniKhata.nonce;

		$.post(FaraniKhata.ajaxurl, data, function (response) {
			if (response.success) {
				$('#modal-entry-overlay').hide();
				$('#form-add-entry')[0].reset();
				$('#contact_id').prop('required', false);
				$('#contact-label').text('Name / Contact (Optional)');
				$('#contact-label').css('color', 'var(--text-main)');

				// Refresh data
				loadDashboardStats();
				loadRecentTransactions();
				loadDailyTransactions();
				loadContacts(); // Update balances
			} else {
				alert('Error: ' + response.data);
			}
		});
	});

	$('#form-add-contact').on('submit', function (e) {
		e.preventDefault();
		var data = $(this).serialize() + '&action=fk_add_contact&nonce=' + FaraniKhata.nonce;

		$.post(FaraniKhata.ajaxurl, data, function (response) {
			if (response.success) {
				$('#modal-contact-overlay').hide();
				$('#form-add-contact')[0].reset();

				// Refresh contacts list
				loadContacts();
				loadContactsDropdown();
			} else {
				alert('Error: ' + response.data);
			}
		});
	});

	// PDF Export via PHP Action (Phase 5)
	$('#btn-export-pdf').on('click', function () {
		var url = FaraniKhata.ajaxurl + '?action=export_ledger_pdf&nonce=' + FaraniKhata.nonce;
		window.open(url, '_blank');
	});

	// Filters
	$('#btn-filter-transactions').on('click', function () {
		loadDailyTransactions($('#filter-date-start').val(), $('#filter-date-end').val());
	});

	$('#filter-contact-name').on('keyup', function () {
		loadContacts($(this).val());
	});

	// Initial Load
	loadDashboardStats();
	loadRecentTransactions();
	loadDailyTransactions();
	loadContacts();

});
