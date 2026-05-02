"""Odoo JSON-RPC Client for EmPay Flask Frontend.

Wraps Odoo's /jsonrpc endpoint so Flask can call any Odoo model method
while respecting Odoo's security groups and record rules.
"""
import json
import requests


class OdooRPC:
    """Thin wrapper around Odoo 19 JSON-RPC API."""

    def __init__(self, url: str, db: str):
        self.url = url.rstrip('/')
        self.db = db
        self.uid = None
        self.login = None
        self.password = None
        self.session = requests.Session()

    # ------------------------------------------------------------------
    # Low-level RPC
    # ------------------------------------------------------------------

    def _jsonrpc(self, endpoint: str, method: str, params: dict) -> dict:
        """Send a JSON-RPC 2.0 request to Odoo."""
        payload = {
            'jsonrpc': '2.0',
            'method': method,
            'params': params,
            'id': 1,
        }
        try:
            resp = self.session.post(
                f'{self.url}{endpoint}',
                json=payload,
                headers={'Content-Type': 'application/json'},
                timeout=30,
            )
            resp.raise_for_status()
            result = resp.json()
        except requests.exceptions.ConnectionError:
            raise ConnectionError('Cannot connect to Odoo at %s' % self.url)
        except requests.exceptions.Timeout:
            raise TimeoutError('Odoo request timed out')
        except Exception as e:
            raise RuntimeError('RPC request failed: %s' % str(e))

        if result.get('error'):
            err = result['error']
            msg = err.get('data', {}).get('message', '') or err.get('message', 'Unknown Odoo error')
            
            # Handle session expiration by re-authenticating once
            if 'Session expired' in msg and self.login and self.password and endpoint != '/web/session/authenticate':
                try:
                    self.authenticate(self.login, self.password)
                    return self._jsonrpc(endpoint, method, params)
                except Exception:
                    pass
            
            raise RuntimeError(msg)
        return result.get('result')

    # ------------------------------------------------------------------
    # Authentication
    # ------------------------------------------------------------------

    def authenticate(self, login: str, password: str) -> int:
        """Authenticate a user against Odoo. Returns uid or raises."""
        result = self._jsonrpc('/web/session/authenticate', 'call', {
            'db': self.db,
            'login': login,
            'password': password,
        })
        uid = result.get('uid')
        if not uid:
            raise ValueError('Invalid credentials')
        self.uid = uid
        self.login = login
        self.password = password
        return uid

    def get_session_info(self) -> dict:
        """Get current session info (user name, groups, etc.)."""
        return self._jsonrpc('/web/session/get_session_info', 'call', {})

    # ------------------------------------------------------------------
    # Model Operations
    # ------------------------------------------------------------------

    def call_kw(self, model: str, method: str, args: list = None,
                kwargs: dict = None) -> any:
        """Call any Odoo model method via JSON-RPC."""
        if not self.uid:
            raise RuntimeError('Not authenticated')
        return self._jsonrpc('/web/dataset/call_kw', 'call', {
            'model': model,
            'method': method,
            'args': args or [],
            'kwargs': kwargs or {},
        })

    def search_read(self, model: str, domain: list = None,
                    fields: list = None, limit: int = 0,
                    offset: int = 0, order: str = '') -> list:
        """Convenience wrapper for search_read."""
        return self.call_kw(model, 'search_read', [], {
            'domain': domain or [],
            'fields': fields or [],
            'limit': limit,
            'offset': offset,
            'order': order,
        })

    def read(self, model: str, ids: list, fields: list = None) -> list:
        """Read specific records by IDs."""
        return self.call_kw(model, 'read', [ids], {
            'fields': fields or [],
        })

    def create(self, model: str, vals: dict) -> int:
        """Create a new record."""
        return self.call_kw(model, 'create', [vals])

    def write(self, model: str, ids: list, vals: dict) -> bool:
        """Update existing records."""
        return self.call_kw(model, 'write', [ids, vals])

    def unlink(self, model: str, ids: list) -> bool:
        """Delete records."""
        return self.call_kw(model, 'unlink', [ids])

    # ------------------------------------------------------------------
    # User info helpers
    # ------------------------------------------------------------------

    def get_user_groups(self, uid: int) -> dict:
        """Check which EmPay security groups a user belongs to."""
        groups = {
            'is_admin': False,
            'is_hr_officer': False,
            'is_payroll_officer': False,
            'is_employee': False,
        }
        try:
            result = self.call_kw('res.users', 'has_group', [uid, 'empay_hrms.group_admin'])
            groups['is_admin'] = bool(result)
        except Exception:
            pass
        try:
            result = self.call_kw('res.users', 'has_group', [uid, 'empay_hrms.group_hr_officer'])
            groups['is_hr_officer'] = bool(result)
        except Exception:
            pass
        try:
            result = self.call_kw('res.users', 'has_group', [uid, 'empay_hrms.group_payroll_officer'])
            groups['is_payroll_officer'] = bool(result)
        except Exception:
            pass
        try:
            result = self.call_kw('res.users', 'has_group', [uid, 'empay_hrms.group_employee'])
            groups['is_employee'] = bool(result)
        except Exception:
            pass
        return groups

    def get_dashboard_stats(self) -> dict:
        """Call the existing get_dashboard_stats method on empay.payrun."""
        return self.call_kw('empay.payrun', 'get_dashboard_stats', [])
