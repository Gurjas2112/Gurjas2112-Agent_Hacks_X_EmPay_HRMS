/** @odoo-module **/

import { registry } from "@web/core/registry";
import { useService } from "@web/core/utils/hooks";
import { Component, onWillStart, onMounted, onWillUnmount, useState } from "@odoo/owl";

// Auto-refresh interval in milliseconds (30 seconds)
const POLL_INTERVAL_MS = 30000;

// Bus channel for real-time notifications
const EMPAY_BUS_CHANNEL = "empay_dashboard";

export class EmpayDashboard extends Component {
    static template = "empay_hrms.EmpayDashboard";

    setup() {
        this.orm = useService("orm");
        this.action = useService("action");
        this.notification = useService("notification");

        // Try to get bus_service — available in Odoo's longpolling/websocket setup
        try {
            this.busService = this.env.services["bus_service"];
        } catch {
            this.busService = null;
        }

        this.state = useState({
            isLoading: true,
            hasError: false,
            errorMessage: "",
            lastUpdated: null,
            autoRefreshEnabled: true,
            is_admin: true,
            employee_data: {},
            stats: {
                total_employees: 0,
                present_today: 0,
                pending_leaves: 0,
                month_net_payroll: 0,
                on_leave_today: 0,
                late_arrivals: 0,
                early_departures: 0,
                last_payrun: { name: "N/A", state: "N/A", total_net: 0 },
                monthly_attendance: [],
                leave_distribution: [],
                payroll_trend: [],
                recent_activities: [],
            },
        });

        this._pollTimerId = null;
        this._boundBusHandler = this._onBusNotification.bind(this);

        onWillStart(async () => {
            await this._fetchDashboardData();
        });

        onMounted(() => {
            // Start auto-polling
            this._startPolling();
            // Subscribe to bus channel for real-time push notifications
            this._subscribeToBus();
        });

        onWillUnmount(() => {
            // Clean up polling timer and bus subscription
            this._stopPolling();
            this._unsubscribeFromBus();
        });
    }

    // ------------------------------------------------------------------
    // Real-Time: Polling
    // ------------------------------------------------------------------

    _startPolling() {
        if (this._pollTimerId) return;
        this._pollTimerId = setInterval(async () => {
            if (this.state.autoRefreshEnabled && !this.state.isLoading) {
                await this._fetchDashboardData(true); // silent refresh
            }
        }, POLL_INTERVAL_MS);
    }

    _stopPolling() {
        if (this._pollTimerId) {
            clearInterval(this._pollTimerId);
            this._pollTimerId = null;
        }
    }

    // ------------------------------------------------------------------
    // Real-Time: Odoo Bus (Longpolling / WebSocket)
    // ------------------------------------------------------------------

    _subscribeToBus() {
        if (!this.busService) return;
        try {
            // Odoo 19 uses addChannel + addEventListener pattern
            this.busService.addChannel(EMPAY_BUS_CHANNEL);
            this.busService.addEventListener("notification", this._boundBusHandler);
        } catch (err) {
            console.warn("EmPay: Bus service subscription failed:", err);
        }
    }

    _unsubscribeFromBus() {
        if (!this.busService) return;
        try {
            this.busService.removeEventListener("notification", this._boundBusHandler);
        } catch (err) {
            // silent
        }
    }

    _onBusNotification(ev) {
        const notifications = ev.detail || [];
        for (const { type, payload } of notifications) {
            if (type !== EMPAY_BUS_CHANNEL) continue;

            const event = payload?.event;
            const message = payload?.message || "";

            // Show toast notification
            if (message) {
                this.notification.add(message, {
                    title: "EmPay Live Update",
                    type: event === "error" ? "danger" : "info",
                    sticky: false,
                });
            }

            // Instantly refresh dashboard data
            this._fetchDashboardData(true);
        }
    }

    // ------------------------------------------------------------------
    // Data Fetching
    // ------------------------------------------------------------------

    async _fetchDashboardData(silent = false) {
        try {
            if (!silent) {
                this.state.isLoading = true;
            }
            this.state.hasError = false;
            const data = await this.orm.call("empay.payrun", "get_dashboard_stats", []);
            this.state.is_admin = data.is_admin;
            this.state.employee_data = data.employee_data;
            this.state.stats = data; // Includes all stats even if some are for admin only
            this.state.lastUpdated = new Date().toLocaleTimeString("en-IN", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            });
        } catch (error) {
            if (!silent) {
                this.state.hasError = true;
                this.state.errorMessage = error.message || "Failed to load dashboard data.";
            }
            console.error("EmPay Dashboard Error:", error);
        } finally {
            if (!silent) {
                this.state.isLoading = false;
            }
        }
    }

    // ------------------------------------------------------------------
    // UI Actions
    // ------------------------------------------------------------------

    async onRefresh() {
        await this._fetchDashboardData();
    }

    toggleAutoRefresh() {
        this.state.autoRefreshEnabled = !this.state.autoRefreshEnabled;
        if (this.state.autoRefreshEnabled) {
            this._startPolling();
            this.notification.add("Auto-refresh enabled (every 30s)", {
                type: "success",
                sticky: false,
            });
        } else {
            this._stopPolling();
            this.notification.add("Auto-refresh paused", {
                type: "warning",
                sticky: false,
            });
        }
    }

    // ------------------------------------------------------------------
    // Formatters & Helpers
    // ------------------------------------------------------------------

    formatCurrency(value) {
        if (value === undefined || value === null) return "₹0";
        return "₹" + Number(value).toLocaleString("en-IN", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        });
    }

    formatTime(datetimeStr) {
        if (!datetimeStr || typeof datetimeStr !== "string") return "--";
        const parts = datetimeStr.split(" ");
        return parts.length > 1 ? parts[1] : datetimeStr;
    }

    getPayrunStateBadge(state) {
        const badges = {
            draft: { label: "Draft", class: "badge-draft" },
            confirmed: { label: "Confirmed", class: "badge-confirmed" },
            paid: { label: "Paid", class: "badge-paid" },
        };
        return badges[state] || { label: state || "N/A", class: "badge-default" };
    }

    getMaxAttendance() {
        const data = this.state.stats.monthly_attendance || [];
        if (!data.length) return 1;
        return Math.max(...data.map((d) => d.count), 1);
    }

    getMaxPayroll() {
        const data = this.state.stats.payroll_trend || [];
        if (!data.length) return 1;
        return Math.max(...data.map((d) => d.total), 1);
    }

    getBarHeight(value, max) {
        if (!max || !value) return "0%";
        return Math.round((value / max) * 100) + "%";
    }

    getTotalLeaves() {
        const data = this.state.stats.leave_distribution || [];
        return data.reduce((sum, d) => sum + d.count, 0) || 1;
    }

    getLeavePercentage(count) {
        const total = this.getTotalLeaves();
        return Math.round((count / total) * 100);
    }

    getLeaveColor(index) {
        const colors = ["#00b9a1", "#1a4a5e", "#f59e0b", "#ef4444", "#8b5cf6", "#3b82f6"];
        return colors[index % colors.length];
    }

    async onClockIn() {
        try {
            await this.orm.call("hr.employee", "attendance_manual", [[this.state.employee_data.id], 'hr_attendance.hr_attendance_action_my_attendances']);
            this.notification.add("Successfully clocked in!", { type: "success" });
            await this._fetchDashboardData(true);
        } catch (error) {
            this.notification.add(error.message || "Clock-in failed", { type: "danger" });
        }
    }

    async onClockOut() {
        try {
            await this.orm.call("hr.employee", "attendance_manual", [[this.state.employee_data.id], 'hr_attendance.hr_attendance_action_my_attendances']);
            this.notification.add("Successfully clocked out!", { type: "success" });
            await this._fetchDashboardData(true);
        } catch (error) {
            this.notification.add(error.message || "Clock-out failed", { type: "danger" });
        }
    }

    openMyAttendance() {
        this.action.doAction("empay_hrms.action_empay_my_attendance");
    }

    // ------------------------------------------------------------------
    // Navigation Actions
    // ------------------------------------------------------------------

    openEmployees() {
        this.action.doAction({
            type: "ir.actions.act_window",
            name: "Employees",
            res_model: "hr.employee",
            view_mode: "list,form",
            views: [[false, "list"], [false, "form"]],
        });
    }

    openAttendance() {
        this.action.doAction({
            type: "ir.actions.act_window",
            name: "Today's Attendance",
            res_model: "hr.attendance",
            view_mode: "list,form",
            views: [[false, "list"], [false, "form"]],
        });
    }

    openLeaves() {
        this.action.doAction({
            type: "ir.actions.act_window",
            name: "Pending Leaves",
            res_model: "hr.leave",
            view_mode: "list,form",
            views: [[false, "list"], [false, "form"]],
            domain: [["state", "=", "confirm"]],
        });
    }

    openPayruns() {
        this.action.doAction({
            type: "ir.actions.act_window",
            name: "Pay Runs",
            res_model: "empay.payrun",
            view_mode: "list,form",
            views: [[false, "list"], [false, "form"]],
        });
    }

    openGeneratePayrun() {
        this.action.doAction("empay_hrms.action_generate_payrun_wizard");
    }

    openPayslips() {
        this.action.doAction({
            type: "ir.actions.act_window",
            name: "Payslips",
            res_model: "empay.payslip",
            view_mode: "list,form",
            views: [[false, "list"], [false, "form"]],
        });
    }
}

registry.category("actions").add("empay_dashboard", EmpayDashboard);
