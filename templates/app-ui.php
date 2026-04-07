<?php
if (!defined('ABSPATH')) {
	exit;
}

$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('farani_khata_nonce');

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex,nofollow">
	<title>Farani Daily Khata</title>
	<!-- Google Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<!-- FontAwesome Icons -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	
	<!-- Plugin Custom CSS -->
	<link rel="stylesheet" href="<?php echo FARANI_KHATA_URL . 'assets/css/style.css'; ?>">
	
	<script>
		var FaraniKhata = {
			ajaxurl: '<?php echo esc_url($ajax_url); ?>',
			nonce: '<?php echo esc_js($nonce); ?>'
		};
	</script>
</head>
<body class="fk-dark-mode">

	<div class="fk-app-container">
		
		<!-- Sidebar -->
		<aside class="fk-sidebar glass-panel">
			<div class="fk-brand">
				<i class="fa-solid fa-wallet"></i>
				<span>Daily Khata</span>
			</div>
			
			<nav class="fk-nav">
				<a href="#" class="fk-nav-item active" data-view="dashboard">
					<i class="fa-solid fa-chart-line"></i> Dashboard
				</a>
				<a href="#" class="fk-nav-item" data-view="customers">
					<i class="fa-solid fa-users"></i> Customers
				</a>
				<a href="#" class="fk-nav-item" data-view="reports">
					<i class="fa-solid fa-file-invoice"></i> Reports
				</a>
				<a href="#" class="fk-nav-item" data-view="settings">
					<i class="fa-solid fa-cog"></i> Settings
				</a>
			</nav>

			<div class="fk-sidebar-footer">
				<a class="fk-logout-btn" href="<?php echo wp_logout_url(); ?>">
					<i class="fa-solid fa-sign-out-alt"></i> Logout
				</a>
			</div>
		</aside>

		<!-- Main Content -->
		<main class="fk-main-content">
			
			<header class="fk-topbar glass-panel">
				<div class="fk-user-info">
					Welcome, <?php echo wp_get_current_user()->display_name; ?>
				</div>
				<button class="fk-btn fk-btn-primary" id="btn-add-entry">
					<i class="fa-solid fa-plus"></i> Add Entry
				</button>
			</header>

			<div class="fk-view-container" id="view-dashboard">
				<h2 class="fk-page-title">Financial Overview</h2>
				<div class="fk-stats-grid">
					<div class="fk-stat-card glass-panel" style="border-left-color: #10b981;">
						<div class="stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
						<div class="stat-info">
							<h4>Total Cash in Hand</h4>
							<h3 id="stat-cash-in-hand">Rs 0</h3>
						</div>
					</div>
					<div class="fk-stat-card glass-panel" style="border-left-color: #3b82f6;">
						<div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
						<div class="stat-info">
							<h4>Total Udhaar to Recover</h4>
							<h3 id="stat-udhaar-recover">Rs 0</h3>
						</div>
					</div>
					<div class="fk-stat-card glass-panel" style="border-left-color: #ef4444;">
						<div class="stat-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
						<div class="stat-info">
							<h4>Total Payable</h4>
							<h3 id="stat-payable">Rs 0</h3>
						</div>
					</div>
				</div>
				
				<div class="fk-dashboard-tables">
					<div class="fk-table-card glass-panel">
						<h3>Recent Transactions</h3>
						<div class="table-responsive">
							<table class="fk-table">
								<thead>
									<tr>
										<th>Date</th>
										<th>Description</th>
										<th>Type</th>
										<th>Amount</th>
									</tr>
								</thead>
								<tbody id="tbl-recent-transactions">
									<!-- Loaded via AJAX -->
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<div class="fk-view-container" id="view-daily-entry" style="display:none;">
				<h2 class="fk-page-title">Daily Transactions</h2>
				<div style="margin-bottom: 20px; display:flex; gap:10px;">
					<input type="date" id="filter-date-start" class="fk-form-control glass-panel" style="padding:8px; border-radius:5px; color:white; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1);">
					<input type="date" id="filter-date-end" class="fk-form-control glass-panel" style="padding:8px; border-radius:5px; color:white; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1);">
					<button class="fk-btn fk-btn-primary" id="btn-filter-transactions">Filter</button>
				</div>
				<div class="fk-table-card glass-panel">
					<div class="table-responsive">
						<table class="fk-table">
							<thead>
								<tr>
									<th>Date</th>
									<th>Description</th>
									<th>Type</th>
									<th>Amount</th>
								</tr>
							</thead>
							<tbody id="tbl-daily-transactions">
								<!-- Loaded via AJAX -->
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="fk-view-container" id="view-udhaar" style="display:none;">
				<div style="display:flex; justify-content:space-between; align-items:center;">
					<h2 class="fk-page-title">Udhaar (Ledger)</h2>
					<button class="fk-btn fk-btn-secondary" id="btn-add-contact"><i class="fa-solid fa-user-plus"></i> New Contact</button>
				</div>
				<div style="margin-bottom: 20px; margin-top: 10px;">
					<input type="text" id="filter-contact-name" placeholder="Search contact name..." class="fk-form-control glass-panel" style="padding:10px; width:100%; max-width:300px; border-radius:5px; color:white; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1);">
				</div>
				
				<div class="fk-table-card glass-panel">
					<div class="table-responsive">
						<table class="fk-table">
							<thead>
								<tr>
									<th>Name</th>
									<th>Phone</th>
									<th>Total Balance</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody id="tbl-contacts">
								<!-- Loaded via AJAX -->
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="fk-view-container" id="view-reports" style="display:none;">
				<div style="display:flex; justify-content:space-between; align-items:center;">
					<h2 class="fk-page-title">Monthly Analytics</h2>
					<button class="fk-btn fk-btn-primary" id="btn-export-pdf"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
				</div>
				
				<div class="fk-table-card glass-panel">
					<div class="table-responsive" id="report-content">
						<table class="fk-table">
							<thead>
								<tr>
									<th>Month</th>
									<th>Income</th>
									<th>Expense</th>
									<th>Net Savings</th>
								</tr>
							</thead>
							<tbody id="tbl-monthly-reports">
								<!-- Loaded via AJAX -->
							</tbody>
						</table>
					</div>
				</div>
			</div>

		</main>
	</div>

	<!-- Mobile Sticky Footer Navigation -->
	<nav class="fk-mobile-nav glass-panel">
		<a href="#" class="fk-mobile-nav-item" data-view="dashboard">
			<i class="fa-solid fa-house"></i>
			<span>Home</span>
		</a>
		<a href="#" class="fk-mobile-nav-item fk-add-btn" id="mobile-add-btn">
			<div class="add-btn-circle">
				<i class="fa-solid fa-plus"></i>
			</div>
			<span>Add Entry</span>
		</a>
		<a href="#" class="fk-mobile-nav-item" data-view="settings">
			<i class="fa-solid fa-user"></i>
			<span>Profile</span>
		</a>
	</nav>

	<!-- Modals -->
	<div class="fk-modal-overlay" id="modal-entry-overlay">
		<div class="fk-modal glass-panel">
			<div class="fk-modal-header">
				<h3><i class="fa-solid fa-plus-circle"></i> Add Entry / Udhaar</h3>
				<span class="fk-modal-close">&times;</span>
			</div>
			<div class="fk-modal-body">
				<form id="form-add-entry">
					<div class="fk-form-group">
						<label>Entry Type</label>
						<select name="entry_type" id="entry_type" required>
							<option value="income">Income</option>
							<option value="expense">Expense</option>
							<option value="udhaar_given">Maine Udhaar Diya (Given)</option>
							<option value="udhaar_returned">Udhaar Wapis Aaya (Returned)</option>
						</select>
					</div>

					<div class="fk-form-group" id="group-contact" style="display:block;">
						<label id="contact-label" style="font-weight: bold; color: var(--text-main);">Name / Contact (Optional)</label>
						<div style="display:flex; gap:10px;">
							<select name="contact_id" id="contact_id" style="flex:1;">
								<option value="">-- Choose Contact --</option>
								<!-- Loaded via AJAX -->
							</select>
							<button type="button" class="fk-btn fk-btn-secondary" id="btn-quick-add-contact"><i class="fa-solid fa-plus"></i></button>
						</div>
					</div>

					<div class="fk-form-group">
						<label>Amount (Rs)</label>
						<input type="number" name="amount" id="amount" step="0.01" required>
					</div>
					
					<div class="fk-form-group">
						<label>Description</label>
						<input type="text" name="description" id="description" required>
					</div>
					
					<div class="fk-form-group">
						<button type="submit" class="fk-btn fk-btn-primary w-100">Save Entry</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Modal for Adding Contact -->
	<div class="fk-modal-overlay" id="modal-contact-overlay">
		<div class="fk-modal glass-panel">
			<div class="fk-modal-header">
				<h3><i class="fa-solid fa-user-plus"></i> Add New Contact</h3>
				<span class="fk-modal-close">&times;</span>
			</div>
			<div class="fk-modal-body">
				<form id="form-add-contact">
					<div class="fk-form-group">
						<label>Name</label>
						<input type="text" name="contact_name" id="contact_name" required>
					</div>
					<div class="fk-form-group">
						<label>Phone</label>
						<input type="text" name="contact_phone" id="contact_phone">
					</div>
					<div class="fk-form-group">
						<button type="submit" class="fk-btn fk-btn-secondary w-100">Save Contact</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Footer branding -->
	<div class="fk-footer-branding">
		Designed and Developed by Sikandar Hayat Baba
	</div>

	<!-- jQuery and Optional Libraries -->
	<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
	<script src="<?php echo FARANI_KHATA_URL . 'assets/js/app.js'; ?>"></script>
</body>
</html>
