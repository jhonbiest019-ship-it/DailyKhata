<?php
if (!defined('ABSPATH')) {
	exit;
}

class Farani_Khata_Ajax
{

	public function __construct()
	{
		$actions = array(
			'fk_add_transaction',
			'fk_get_transactions',
			'fk_edit_transaction',
			'fk_delete_transaction',
			'export_ledger_pdf',

			'fk_get_stats',
			'fk_get_contacts',
			'fk_get_reports',
			'fk_add_contact'
		);

		foreach ($actions as $action) {
			add_action("wp_ajax_{$action}", array($this, $action));
			add_action("wp_ajax_nopriv_{$action}", array($this, $action));
		}
	}

	private function verify_nonce()
	{
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'farani_khata_nonce')) {
			wp_send_json_error('Invalid security token');
			exit;
		}
	}

	// -------------------------------------------------------------
	// CORE CRUD ENDPOINTS (PHASE 4 REQUIRED)
	// -------------------------------------------------------------

	public function fk_add_transaction()
	{
		$this->verify_nonce();
		global $wpdb;

		$form_type = sanitize_text_field($_POST['entry_type']); // income, expense, udhaar_given, udhaar_returned
		$amount = floatval($_POST['amount']);
		
		$category = 'udhaar';
		$db_type = 'debit';
		
		if ($form_type === 'income') {
			$db_type = 'credit';
			$category = 'income';
		} elseif ($form_type === 'expense') {
			$db_type = 'debit';
			$category = 'expense';
		} elseif ($form_type === 'udhaar_given') {
			$db_type = 'debit';
			$category = 'udhaar';
			$amount = abs($amount); // Record positive for udhaar given
		} elseif ($form_type === 'udhaar_returned') {
			$db_type = 'credit';
			$category = 'udhaar';
			$amount = -abs($amount); // Record negative for udhaar returned
		}

		$description = sanitize_textarea_field($_POST['description']);
		$customer_id = !empty($_POST['contact_id']) ? intval($_POST['contact_id']) : null;

		if ($category === 'udhaar' && !$customer_id) {
			wp_send_json_error('Contact is required for Udhaar.');
		}

		$t_table = $wpdb->prefix . 'khata_entries';
		$inserted = $wpdb->insert(
			$t_table,
			array(
			'type' => $db_type,
			'category' => $category,
			'amount' => $amount,
			'description' => $description,
			'customer_id' => $customer_id ? $customer_id : null
		),
			array('%s', '%s', '%f', '%s', '%d')
		);

		if (!$inserted) {
			wp_send_json_error('Failed to insert transaction.');
		}

		wp_send_json_success('Transaction saved.');
	}

	public function fk_get_transactions()
	{
		$this->verify_nonce();
		global $wpdb;
		$table = $wpdb->prefix . 'khata_entries';
		$c_table = $wpdb->prefix . 'khata_customers';
		
		$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 100;
		$start_date = !empty($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
		$end_date = !empty($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

		$where = "1=1";
		if ($start_date)
			$where .= $wpdb->prepare(" AND DATE(transaction_date) >= %s", $start_date);
		if ($end_date)
			$where .= $wpdb->prepare(" AND DATE(transaction_date) <= %s", $end_date);

		$query = "
			SELECT e.*, c.name as customer_name 
			FROM $table e 
			LEFT JOIN $c_table c ON e.customer_id = c.id 
			WHERE $where 
			ORDER BY transaction_date DESC LIMIT $limit
		";

		$results = $wpdb->get_results($query);

		$data = array();
		foreach ($results as $row) {
			$desc = stripslashes($row->description);
			if (!empty($row->customer_name)) {
				$desc = $row->customer_name . ' - ' . $desc;
			}
			$data[] = array(
				'id' => $row->id,
				'type' => $row->category, // using category as type representation for UI like 'income', 'expense'
				'db_type' => $row->type,
				'amount' => $row->amount,
				'description' => $desc,
				'date' => date('d M Y, H:i', strtotime($row->transaction_date))
			);
		}

		wp_send_json_success($data);
	}

	public function fk_edit_transaction()
	{
		$this->verify_nonce();
		global $wpdb;

		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$amount = floatval($_POST['amount']);
		$description = sanitize_text_field($_POST['description']);
		$t_table = $wpdb->prefix . 'khata_entries';

		if ($id > 0) {
			$updated = $wpdb->update(
				$t_table,
				array('amount' => $amount, 'description' => $description),
				array('id' => $id),
				array('%f', '%s'),
				array('%d')
			);
			if ($updated !== false) {
				wp_send_json_success('Transaction updated.');
			}
		}
		wp_send_json_error('Failed to update transaction.');
	}

	public function fk_delete_transaction()
	{
		$this->verify_nonce();
		global $wpdb;

		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$t_table = $wpdb->prefix . 'khata_entries';

		if ($id > 0) {
			$deleted = $wpdb->delete($t_table, array('id' => $id), array('%d'));
			if ($deleted) {
				wp_send_json_success('Transaction deleted.');
			}
		}
		wp_send_json_error('Failed to delete transaction.');
	}

	// -------------------------------------------------------------
	// SUPPORT & DASHBOARD ENDPOINTS
	// -------------------------------------------------------------

	public function fk_get_stats()
	{
		$this->verify_nonce();
		global $wpdb;
		$t_table = $wpdb->prefix . 'khata_entries';

		// All entries that are income
		$income = $wpdb->get_var("SELECT SUM(amount) FROM $t_table WHERE category = 'income'");
		// All entries that are expense
		$expense = $wpdb->get_var("SELECT SUM(amount) FROM $t_table WHERE category = 'expense'");
		// Udhaar total: wait, udhaar changes balance. We gave money (positive amount), received (negative amount)
		$udhaar_given = $wpdb->get_var("SELECT SUM(amount) FROM $t_table WHERE category = 'udhaar' AND amount > 0");
		$udhaar_recv = $wpdb->get_var("SELECT SUM(ABS(amount)) FROM $t_table WHERE category = 'udhaar' AND amount < 0");

		$cash_in_hand = floatval($income) - floatval($expense) - floatval($udhaar_given) + floatval($udhaar_recv);

		// Calculate total recover and payable by grouping customer balances
		$recover = 0;
		$payable = 0;

		$customer_balances = $wpdb->get_results("SELECT customer_id, SUM(amount) as bal FROM $t_table WHERE category = 'udhaar' GROUP BY customer_id");
		foreach ($customer_balances as $cb) {
			if (floatval($cb->bal) > 0)
				$recover += floatval($cb->bal);
			if (floatval($cb->bal) < 0)
				$payable += abs(floatval($cb->bal));
		}

		wp_send_json_success(array(
			'cash_in_hand' => number_format($cash_in_hand, 2),
			'udhaar_to_recover' => number_format($recover, 2),
			'payable' => number_format($payable, 2)
		));
	}

	public function fk_get_contacts()
	{
		$this->verify_nonce();
		global $wpdb;
		$c_table = $wpdb->prefix . 'khata_customers';
		$t_table = $wpdb->prefix . 'khata_entries';
		$search = !empty($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

		$where = "1=1";
		if ($search) {
			$where .= $wpdb->prepare(" AND name LIKE %s", '%' . $wpdb->esc_like($search) . '%');
		}

		$results = $wpdb->get_results("SELECT * FROM $c_table WHERE $where ORDER BY name ASC");

		// Map balances
		foreach ($results as &$c) {
			$bal = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM $t_table WHERE customer_id = %d AND category = 'udhaar'", $c->id));
			$c->total_balance = floatval($bal);

			// Phase 5 WhatsApp Integration
			if (!empty($c->phone)) {
				// remove non-numeric chars for wa.me
				$clean_phone = preg_replace('/[^0-9]/', '', $c->phone);
				$msg = urlencode("Salaam, your pending balance in my Khata is Rs " . $c->total_balance . ".");
				$c->wa_link = "https://wa.me/{$clean_phone}?text={$msg}";
			}
			else {
				$c->wa_link = "#";
			}
		}

		wp_send_json_success($results);
	}

	public function fk_get_reports()
	{
		$this->verify_nonce();
		global $wpdb;
		$table = $wpdb->prefix . 'khata_entries';

		$query = "
			SELECT 
				DATE_FORMAT(transaction_date, '%M %Y') as month_year,
				DATE_FORMAT(transaction_date, '%Y-%m') as my_sort,
				SUM(CASE WHEN category='income' THEN amount ELSE 0 END) as income,
				SUM(CASE WHEN category='expense' THEN amount ELSE 0 END) as expense
			FROM $table
			GROUP BY my_sort, month_year
			ORDER BY my_sort DESC
		";

		$results = $wpdb->get_results($query);
		$data = array();
		foreach ($results as $r) {
			$data[] = array(
				'month_year' => $r->month_year,
				'income' => number_format(floatval($r->income), 2),
				'expense' => number_format(floatval($r->expense), 2),
				'net' => number_format(floatval($r->income) - floatval($r->expense), 2)
			);
		}
		wp_send_json_success($data);
	}

	public function fk_add_contact()
	{
		$this->verify_nonce();
		global $wpdb;

		$name = sanitize_text_field($_POST['contact_name']);
		$phone = sanitize_text_field($_POST['contact_phone']);

		if (empty($name))
			wp_send_json_error('Name is required.');

		$table = $wpdb->prefix . 'khata_customers';
		$inserted = $wpdb->insert(
			$table,
			array(
			'name' => $name,
			'phone' => $phone
		),
			array('%s', '%s')
		);

		if ($inserted) {
			wp_send_json_success('Contact added.');
		}
		else {
			wp_send_json_error('Failed to add contact.');
		}
	}

	public function export_ledger_pdf()
	{
		// lightweight HTML-to-PDF print engine
		if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'farani_khata_nonce')) {
			die('Security Check Failed');
		}

		global $wpdb;
		$table = $wpdb->prefix . 'khata_entries';

		$query = "
			SELECT 
				DATE_FORMAT(transaction_date, '%M %Y') as month_year,
				DATE_FORMAT(transaction_date, '%Y-%m') as my_sort,
				SUM(CASE WHEN category='income' THEN amount ELSE 0 END) as income,
				SUM(CASE WHEN category='expense' THEN amount ELSE 0 END) as expense
			FROM $table
			GROUP BY my_sort, month_year
			ORDER BY my_sort DESC
		";
		$results = $wpdb->get_results($query);
?>
		<!DOCTYPE html>
		<html>
		<head>
			<title>Monthly Analytics Receipt</title>
			<style>
				body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#333; padding: 20px; }
				h2 { text-align: center; color: #1a1a2e; }
				p { text-align: center; font-size: 14px; color: #666; }
				table { width: 100%; border-collapse: collapse; margin-top: 30px; }
				th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
				th { background-color: #1a1a2e; color: white; }
				.success { color: #0f9d58; }
				.danger { color: #d93025; }
			</style>
		</head>
		<body>
			<h2>Farani Daily Khata - Analytics Receipt</h2>
			<p>Generated on <?php echo date('d M Y, h:i A'); ?></p>
			<table>
				<thead>
					<tr>
						<th>Month</th>
						<th>Income (Rs)</th>
						<th>Expense (Rs)</th>
						<th>Net Savings (Rs)</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($results as $r):
			$inc = floatval($r->income);
			$exp = floatval($r->expense);
			$net = $inc - $exp;
?>
					<tr>
						<td><?php echo esc_html($r->month_year); ?></td>
						<td class="success"><?php echo number_format($inc, 2); ?></td>
						<td class="danger"><?php echo number_format($exp, 2); ?></td>
						<td class="<?php echo $net >= 0 ? 'success' : 'danger'; ?>"><?php echo number_format($net, 2); ?></td>
					</tr>
					<?php
		endforeach; ?>
				</tbody>
			</table>
			<script>
				window.onload = function() {
					window.print();
				}
			</script>
		</body>
		</html>
		<?php
		exit;
	}
}
