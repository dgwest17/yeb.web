/**
 * admin-login.js — Admin Panel Authentication Client
 * 
 * HOW TO INTEGRATE INTO admin.html:
 * 
 * 1. Add this script tag before your existing admin JS:
 *    <script src="admin-login.js"></script>
 * 
 * 2. Replace your current password prompt logic with:
 *    Call adminAuth.init() on page load.
 * 
 * 3. For all save/fetch calls, add the CSRF header:
 *    headers: { 'X-CSRF-Token': adminAuth.csrfToken }
 * 
 * 4. Add a logout button somewhere in admin.html:
 *    <button onclick="adminAuth.logout()">Logout</button>
 */

const adminAuth = {
    csrfToken: null,
    authenticated: false,

    /**
     * Initialize — check session, show login if needed
     */
    async init() {
        try {
            const res = await fetch('/admin-auth.php?action=check');
            const data = await res.json();

            if (data.authenticated) {
                this.authenticated = true;
                this.csrfToken = data.csrf_token;
                this.showAdminPanel();
            } else {
                this.showLoginForm();
            }
        } catch (err) {
            console.error('Auth check failed:', err);
            this.showLoginForm();
        }
    },

    /**
     * Show the login overlay
     */
    showLoginForm() {
        // Hide main admin content
        const mainContent = document.getElementById('admin-main') 
            || document.querySelector('.admin-container')
            || document.querySelector('main');
        if (mainContent) mainContent.style.display = 'none';

        // Remove existing login overlay if present
        const existing = document.getElementById('admin-login-overlay');
        if (existing) existing.remove();

        // Create login overlay
        const overlay = document.createElement('div');
        overlay.id = 'admin-login-overlay';
        overlay.innerHTML = `
            <div style="
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: #0a0a0a;
                display: flex; align-items: center; justify-content: center;
                z-index: 99999;
                font-family: 'DM Sans', sans-serif;
            ">
                <div style="
                    background: rgba(255,255,255,0.03);
                    border: 1px solid rgba(34,168,179,0.15);
                    border-radius: 20px;
                    padding: 3rem 2.5rem;
                    max-width: 400px;
                    width: 90%;
                ">
                    <h1 style="
                        font-family: 'Playfair Display', serif;
                        font-size: 1.8rem;
                        margin-bottom: 0.5rem;
                        background: linear-gradient(135deg, #22A8B3, #FB9B47);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    ">Admin Panel</h1>
                    <p style="color: rgba(255,255,255,0.5); margin-bottom: 2rem; font-size: 0.9rem;">
                        Your Energy Best — CMS
                    </p>
                    
                    <div id="admin-login-error" style="
                        display: none;
                        background: rgba(255,59,48,0.1);
                        border: 1px solid rgba(255,59,48,0.3);
                        color: #ff6b6b;
                        padding: 0.75rem 1rem;
                        border-radius: 8px;
                        margin-bottom: 1rem;
                        font-size: 0.9rem;
                    "></div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display:block; font-size:0.85rem; color:rgba(255,255,255,0.6); margin-bottom:0.5rem; text-transform:uppercase; letter-spacing:0.05em;">Username</label>
                        <input type="text" id="admin-username" style="
                            width:100%; padding:0.85rem 1rem;
                            background:rgba(255,255,255,0.05);
                            border:1px solid rgba(34,168,179,0.2);
                            border-radius:10px; color:#fff; font-size:1rem;
                        " autocomplete="username">
                    </div>
                    
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display:block; font-size:0.85rem; color:rgba(255,255,255,0.6); margin-bottom:0.5rem; text-transform:uppercase; letter-spacing:0.05em;">Password</label>
                        <input type="password" id="admin-password" style="
                            width:100%; padding:0.85rem 1rem;
                            background:rgba(255,255,255,0.05);
                            border:1px solid rgba(34,168,179,0.2);
                            border-radius:10px; color:#fff; font-size:1rem;
                        " autocomplete="current-password">
                    </div>

                    <button onclick="adminAuth.login()" style="
                        width:100%; padding:0.9rem;
                        background:linear-gradient(135deg, #22A8B3, #1a8a93);
                        color:#fff; border:none; border-radius:10px;
                        font-size:1rem; font-weight:700; cursor:pointer;
                        font-family:'DM Sans',sans-serif;
                    ">Log In</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        // Enter key support
        const pwField = document.getElementById('admin-password');
        const unField = document.getElementById('admin-username');
        pwField.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') this.login();
        });
        unField.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') document.getElementById('admin-password').focus();
        });

        // Focus username
        setTimeout(() => unField.focus(), 100);
    },

    /**
     * Attempt login
     */
    async login() {
        const username = document.getElementById('admin-username').value.trim();
        const password = document.getElementById('admin-password').value;
        const errorDiv = document.getElementById('admin-login-error');

        if (!username || !password) {
            errorDiv.textContent = 'Please enter both username and password.';
            errorDiv.style.display = 'block';
            return;
        }

        try {
            const res = await fetch('/admin-auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });

            const data = await res.json();

            if (data.success) {
                this.authenticated = true;
                this.csrfToken = data.csrf_token;
                
                // Remove login overlay, show admin panel
                const overlay = document.getElementById('admin-login-overlay');
                if (overlay) overlay.remove();
                this.showAdminPanel();
            } else {
                errorDiv.textContent = data.error || 'Login failed.';
                errorDiv.style.display = 'block';
                document.getElementById('admin-password').value = '';
            }
        } catch (err) {
            errorDiv.textContent = 'Connection error. Please try again.';
            errorDiv.style.display = 'block';
        }
    },

    /**
     * Show the admin panel content
     */
    showAdminPanel() {
        const mainContent = document.getElementById('admin-main')
            || document.querySelector('.admin-container')
            || document.querySelector('main');
        if (mainContent) mainContent.style.display = '';

        // Remove login overlay if present
        const overlay = document.getElementById('admin-login-overlay');
        if (overlay) overlay.remove();
    },

    /**
     * Logout
     */
    async logout() {
        try {
            await fetch('/admin-auth.php?action=logout', { method: 'POST' });
        } catch (e) { /* ignore */ }
        
        this.authenticated = false;
        this.csrfToken = null;
        window.location.reload();
    },

    /**
     * Make an authenticated save request
     * Drop-in replacement for your existing save calls.
     * 
     * Usage:
     *   await adminAuth.save(dataObject);
     * 
     * where dataObject includes _save_target
     */
    async save(data) {
        if (!this.authenticated || !this.csrfToken) {
            alert('Session expired. Please log in again.');
            this.showLoginForm();
            return null;
        }

        try {
            const res = await fetch('/save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(data)
            });

            if (res.status === 403) {
                alert('Session expired. Please log in again.');
                this.showLoginForm();
                return null;
            }

            return await res.json();
        } catch (err) {
            console.error('Save failed:', err);
            alert('Save failed. Please try again.');
            return null;
        }
    },

    /**
     * Upload an image with auth
     * Drop-in replacement for your existing image upload.
     */
    async uploadImage(file) {
        if (!this.authenticated || !this.csrfToken) {
            alert('Session expired. Please log in again.');
            this.showLoginForm();
            return null;
        }

        const formData = new FormData();
        formData.append('image', file);
        formData.append('_csrf_token', this.csrfToken);

        try {
            const res = await fetch('/upload-image.php', {
                method: 'POST',
                body: formData
            });

            if (res.status === 403) {
                alert('Session expired. Please log in again.');
                this.showLoginForm();
                return null;
            }

            return await res.json();
        } catch (err) {
            console.error('Upload failed:', err);
            return null;
        }
    }
};

// ─── Auto-initialize on DOM ready ───
document.addEventListener('DOMContentLoaded', () => {
    adminAuth.init();
});
