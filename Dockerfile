# ============================================================
# EmPay HRMS - Dockerfile for Odoo 19.0 Community Edition
# ============================================================
# This Dockerfile sets up Odoo 19.0 CE with the empay_hrms
# custom module pre-installed and ready to run.
# ============================================================

FROM odoo:19.0

# Maintainer
LABEL maintainer="EmPay Team <team@empay.example.com>"
LABEL description="EmPay HRMS - Smart Human Resource Management System on Odoo 19.0"

# Switch to root for file operations
USER root

# Install system dependencies (curl for healthcheck)
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Create custom addons directory and copy module
RUN mkdir -p /mnt/extra-addons
COPY ./empay_hrms /mnt/extra-addons/empay_hrms

# Copy custom Odoo configuration
COPY ./docker/odoo.conf /etc/odoo/odoo.conf

# Set correct permissions
RUN chown -R odoo:odoo /mnt/extra-addons \
    && chown odoo:odoo /etc/odoo/odoo.conf

# Switch back to odoo user
USER odoo

# Expose Odoo ports
# 8069 = HTTP (web interface)
# 8071 = Longpolling (real-time bus notifications)
# 8072 = WebSocket (Odoo 19 uses this for bus)
EXPOSE 8069 8071 8072

# Health check — verifies Odoo is responding
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8069/web/health || exit 1

# Default command: run Odoo with the custom config
CMD ["odoo", "--config=/etc/odoo/odoo.conf"]
