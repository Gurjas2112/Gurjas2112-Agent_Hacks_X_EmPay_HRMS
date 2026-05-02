<?php
/**
 * EmPay HRMS - Email Professional Payslip Handler
 * Generates an analytical, high-fidelity salary report with PF and Tax calculations
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
require_once __DIR__ . '/../../utils/mailer.php';

requireRole(ROLE_ADMIN, ROLE_PAYROLL);

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlash('error', 'Invalid payslip selection.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT p.*, u.full_name, u.email, u.designation, d.name as dept_name 
                      FROM payroll p 
                      JOIN users u ON p.user_id = u.id 
                      LEFT JOIN departments d ON u.department_id = d.id
                      WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    setFlash('error', 'Payslip not found.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$monthName = date('F Y', strtotime($p['month'] . '-01'));
$basic = $p['net_salary'] * 0.6; // Assuming 60% is basic for demo calculation
$pf = $basic * 0.12; // 12% PF Contribution
$profTax = 200; // Fixed Professional Tax for demo
$gross = $p['net_salary'] + $pf + $profTax; 

$subject = "EmPay Official Payslip - $monthName — " . $p['full_name'];

// Analytical Payslip Template
$body = "
<div style='font-family: Inter, Segoe UI, Arial, sans-serif; max-width: 700px; margin: 20px auto; border: 1px solid #ddd; border-radius: 12px; overflow: hidden; background: #fff;'>
    <!-- Header -->
    <div style='background: linear-gradient(135deg, #714B67 0%, #4C3254 100%); padding: 30px; color: white;'>
        <table style='width: 100%;'>
            <tr>
                <td>
                    <h1 style='margin: 0; font-size: 24px; letter-spacing: 1px;'>EmPay HRMS</h1>
                    <p style='margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;'>Smart Human Resource Management System</p>
                </td>
                <td style='text-align: right;'>
                    <div style='background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; display: inline-block;'>
                        <span style='font-size: 12px; font-weight: bold;'>CONFIDENTIAL</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div style='padding: 30px;'>
        <!-- Employee Info -->
        <table style='width: 100%; margin-bottom: 30px; background: #fcfcfc; padding: 15px; border-radius: 8px; border: 1px solid #eee;'>
            <tr>
                <td style='width: 50%;'>
                    <p style='margin: 0; color: #888; font-size: 11px; text-transform: uppercase;'>Employee Details</p>
                    <p style='margin: 5px 0 0 0; font-size: 16px; font-weight: bold; color: #333;'>" . htmlspecialchars($p['full_name']) . "</p>
                    <p style='margin: 2px 0 0 0; font-size: 13px; color: #666;'>" . htmlspecialchars($p['designation']) . " | " . htmlspecialchars($p['dept_name']) . "</p>
                </td>
                <td style='text-align: right;'>
                    <p style='margin: 0; color: #888; font-size: 11px; text-transform: uppercase;'>Payrun Period</p>
                    <p style='margin: 5px 0 0 0; font-size: 16px; font-weight: bold; color: #714B67;'>$monthName</p>
                    <p style='margin: 2px 0 0 0; font-size: 13px; color: #666;'>Payrun ID: EMP-" . $p['id'] . "-" . date('y', strtotime($p['month'])) . "</p>
                </td>
            </tr>
        </table>

        <!-- Analytical Breakdown -->
        <h3 style='font-size: 14px; color: #714B67; border-bottom: 2px solid #F3EEF1; padding-bottom: 8px; margin-bottom: 15px;'>Earnings & Deductions Analytics</h3>
        
        <table style='width: 100%; border-collapse: collapse; margin-bottom: 30px;'>
            <thead>
                <tr style='background: #F3EEF1;'>
                    <th style='padding: 12px; text-align: left; font-size: 13px;'>Description</th>
                    <th style='padding: 12px; text-align: right; font-size: 13px;'>Amount (INR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style='padding: 12px; border-bottom: 1px solid #f5f5f5;'>Basic Wage (60%)</td>
                    <td style='padding: 12px; border-bottom: 1px solid #f5f5f5; text-align: right;'>₹ " . number_format($basic, 2) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px; border-bottom: 1px solid #f5f5f5;'>Allowances & Overtime</td>
                    <td style='padding: 12px; border-bottom: 1px solid #f5f5f5; text-align: right;'>₹ " . number_format($p['net_salary'] - $basic, 2) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px; border-bottom: 1px solid #f5f5f5; color: #dc3545;'>Provident Fund (PF) Contribution (12%)</td>
                    <td style='padding: 12px; border-bottom: 1px solid #f5f5f5; text-align: right; color: #dc3545;'>- ₹ " . number_format($pf, 2) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px; border-bottom: 1px solid #f5f5f5; color: #dc3545;'>Professional Tax (PT)</td>
                    <td style='padding: 12px; border-bottom: 1px solid #f5f5f5; text-align: right; color: #dc3545;'>- ₹ " . number_format($profTax, 2) . "</td>
                </tr>
            </tbody>
            <tfoot>
                <tr style='background: #714B67; color: white;'>
                    <td style='padding: 15px; font-size: 16px; font-weight: bold;'>NET DISBURSED PAY</td>
                    <td style='padding: 15px; font-size: 16px; font-weight: bold; text-align: right;'>₹ " . number_format($p['net_salary'], 2) . "</td>
                </tr>
            </tfoot>
        </table>

        <!-- Terminology Guide -->
        <div style='background: #fdfdfd; border: 1px dashed #ccc; padding: 20px; border-radius: 8px;'>
            <h4 style='margin: 0 0 10px 0; font-size: 12px; color: #888;'>EMPAY TERMINOLOGY GUIDE</h4>
            <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 11px; line-height: 1.4;'>
                <div>
                    <p><strong>Payroll:</strong> Salary calculation based on attendance records.</p>
                    <p><strong>Payrun:</strong> Specific processing cycle (processed as " . htmlspecialchars($p['status']) . ").</p>
                    <p><strong>Wage:</strong> Compensation including Time-Off adjustments.</p>
                </div>
                <div>
                    <p><strong>PF Contribution:</strong> 12% of basic salary for long-term savings.</p>
                    <p><strong>Professional Tax:</strong> State-levied income tax deducted from gross.</p>
                    <p><strong>Payslip:</strong> This official document breakdown.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style='background: #f8f8f8; padding: 20px; text-align: center; font-size: 11px; color: #999;'>
        <p>This is a system-generated secure Payslip. Generated on " . date('d-M-Y H:i') . "</p>
        <p>&copy; " . date('Y') . " EmPay HRMS — Smart Workplace Solutions</p>
    </div>
</div>
";

// Use the employee's actual email if it's not a demo or use the user provided business email
$targetEmail = $p['email'] ?: 'gsgbmcc@gmail.com';

if (sendEmPayEmail($targetEmail, $subject, $body, 'payroll')) {
    setFlash('success', "Professional Analytical Payslip for " . htmlspecialchars($p['full_name']) . " dispatched successfully.");
} else {
    setFlash('error', "Failed to send the payslip. Please verify SMTP credentials.");
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
