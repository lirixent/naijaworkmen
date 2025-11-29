// =============================================================
// NaijaWorkMen — app.js
// Full script (cleaned, consolidated, commented)
// No-stone-left-unturned: fixed login routing (admin/worker/grad)
// =============================================================

// -----------------------------
// Basic client-side routing
// -----------------------------
document.addEventListener('click', e => {
    const routeBtn = e.target.closest('[data-route]');
    if (routeBtn) {
        e.preventDefault();
        const route = routeBtn.getAttribute('data-route');
        showPage(route);
    }
});

function showPage(id) {
    document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
    const el = document.getElementById(id);
    if (el) el.classList.remove('hidden');
    window.scrollTo(0, 0);
}

// -----------------------------
// Hamburger menu toggle
// -----------------------------
function toggleMenu() {
    const nav = document.getElementById("navMenu");
    if (nav) nav.classList.toggle("show");
}

// -----------------------------
// nav links (explicit handlers, optional)
// -----------------------------
const homeLink = document.getElementById('nav-home');
if (homeLink) {
    homeLink.addEventListener('click', (e) => {
        e.preventDefault();
        showPage('home');
    });
}
const regLink = document.getElementById('nav-register');
if (regLink) {
    regLink.addEventListener('click', (e) => {
        e.preventDefault();
        showPage('register-worker');
    });
}


// =============================
// PASSWORD MATCH + STRENGTH CHECK
// =============================
const allowedSpecials = "@#$%&*!?";

function isStrongPassword(pw) {
    const lengthOK = pw.length >= 8;
    const upperOK  = /[A-Z]/.test(pw);
    const lowerOK  = /[a-z]/.test(pw);
    const digitOK  = /[0-9]/.test(pw);
    const specialOK = new RegExp("[" + allowedSpecials.split("").join("\\") + "]").test(pw);
    const noSpaces = !/\s/.test(pw);
    return lengthOK && upperOK && lowerOK && digitOK && specialOK && noSpaces;
}

// Real-time password border indicator
const pwField = document.getElementById('password');
if (pwField) {
    pwField.addEventListener('input', function () {
        if (isStrongPassword(this.value)) {
            this.style.border = "2px solid #28a745"; // green
        } else {
            this.style.border = "2px solid #dc3545"; // red
        }
    });
}

// Show / Hide password toggle
document.querySelectorAll('.togglePw').forEach(button => {
    button.addEventListener('click', function () {
        const targetId = this.dataset.target;
        const input = document.getElementById(targetId);
        if (!input) return; // safeguard

        if (input.type === "password") {
            input.type = "text";     // reveal password
            this.textContent = "🙈"; // change icon
        } else {
            input.type = "password"; // hide password
            this.textContent = "👁️"; // revert icon
        }
    });
});


// =============================
// Other services routing
// =============================
const otherBtn = document.querySelector('.other-services-card button');
if (otherBtn) {
    otherBtn.addEventListener('click', () => {
        showPage('other-services');
    });
}


// =============================
// Photo preview for registration
// =============================
const photoInput = document.getElementById('photoUpload');
const photoPreview = document.getElementById('photoPreview');
if (photoInput && photoPreview) {
    photoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        } else {
            photoPreview.src = "images/placeholder-passport.jpg";
        }
    });
}


// =============================
// SHOW VERIFICATION PENDING (SPA)
// =============================
function showVerificationPending() {
    document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
    const el = document.getElementById("verification-pending");
    if (el) el.style.display = "block";
}


// =============================
// Resend verification email (standalone button if exists)
// =============================
const resendBtn = document.getElementById("resendVerificationBtn");
if (resendBtn) {
    resendBtn.addEventListener("click", async () => {
        const email = localStorage.getItem("pending_email");
        if (!email) return;

        try {
            const res = await fetch("php/resend_verification.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({email})
            });

            const data = await res.json();
            const msg = document.getElementById("verificationPendingMsg");
            if (msg) {
                msg.style.display = "block";
                msg.style.color = data.status === "success" ? "green" : "red";
                msg.innerText = data.message;
            }

        } catch {
            const msg = document.getElementById("verificationPendingMsg");
            if (msg) {
                msg.style.display = "block";
                msg.style.color = "red";
                msg.innerText = "Unable to resend verification email. Try again later.";
            }
        }
    });
}


// =======================================================
// REGISTRATION: Worker form (one canonical handler)
// =======================================================
document.addEventListener("DOMContentLoaded", () => {
    const workerForm = document.getElementById("workerForm");
    const registrationSection = document.getElementById("register-worker");
    const verificationPendingSection = document.getElementById("verification-pending");

    if (workerForm) {
        // Create a message box if not already present
        let messageBox = document.getElementById("workerMessageBox");
        if (!messageBox) {
            messageBox = document.createElement("div");
            messageBox.id = "workerMessageBox";
            messageBox.style.display = "none";
            messageBox.style.padding = "10px";
            messageBox.style.margin = "10px 0";
            messageBox.style.borderRadius = "5px";
            if (registrationSection) registrationSection.insertBefore(messageBox, workerForm);
        }

        workerForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            // Password checks
            const pwEl = document.getElementById('password');
            const cpwEl = document.getElementById('confirm_password');
            const pw  = pwEl ? pwEl.value : "";
            const cpw = cpwEl ? cpwEl.value : "";

            if (pw !== cpw) {
                messageBox.style.display = "block";
                messageBox.style.background = "#ffdddd";
                messageBox.style.color = "#d8000c";
                messageBox.innerText = "Passwords do not match.";
                return;
            }
            if (!isStrongPassword(pw)) {
                messageBox.style.display = "block";
                messageBox.style.background = "#ffdddd";
                messageBox.style.color = "#d8000c";
                messageBox.innerText = `Weak Password. Must contain at least 8 chars, uppercase, lowercase, number, special: ${allowedSpecials}, no spaces.`;
                return;
            }

            const formData = new FormData(workerForm);

            try {
                const res = await fetch("php/register_worker.php", {
                    method: "POST",
                    body: formData
                });

                const result = await res.json();

                messageBox.style.display = "block";

                if (result.status === "error") {
                    messageBox.style.background = "#ffdddd";
                    messageBox.style.color = "#d8000c";
                    messageBox.innerText = result.message;
                }

                if (result.status === "success") {
                    messageBox.style.background = "#ddffdd";
                    messageBox.style.color = "#270";
                    messageBox.innerText = result.message;

                    // Save email for resend verification
                    const emailField = workerForm.querySelector("[name=email]");
                    if (emailField) localStorage.setItem("pending_email", emailField.value);

                    // Hide registration and show verification pending
                    setTimeout(() => {
                        if (registrationSection) registrationSection.style.display = "none";
                        if (verificationPendingSection) verificationPendingSection.style.display = "block";
                    }, 1200);
                }

            } catch (err) {
                messageBox.style.display = "block";
                messageBox.style.background = "#ffdddd";
                messageBox.style.color = "#d8000c";
                messageBox.innerText = "Something went wrong. Please try again later.";
                console.error(err);
            }
        });

        // Back from verification to registration
        const backBtn = document.getElementById("goBackToRegister");
        if (backBtn) {
            backBtn.addEventListener("click", () => {
                if (verificationPendingSection) verificationPendingSection.style.display = "none";
                if (registrationSection) registrationSection.style.display = "block";
                if (messageBox) messageBox.style.display = "none";
            });
        }
    }
});


// =============================
// Global UI helpers: notify & loader
// =============================
function notify(text, type="info") {
    const box = document.getElementById("globalNotify");
    if (!box) return;

    box.style.background = 
        type === "success" ? "#28a745" :
        type === "error" ? "#dc3545" :
        "#333";

    box.textContent = text;
    box.style.top = "20px";

    setTimeout(() => {
        box.style.top = "-80px";
    }, 3000);
}

function showLoader(show=true) {
    const overlay = document.getElementById("loadingOverlay");
    if (!overlay) return;
    overlay.style.display = show ? "flex" : "none";
}


// =======================================================
// DUPLICATE HANDLER WARNING & CLEANUP
// =======================================================
// You previously had a second workerForm submit handler (below) that would
// create duplicate listeners if both exist. We commented the duplicate out
// earlier and used the canonical one inside DOMContentLoaded.
// If you intentionally need both behaviors, re-enable carefully.
// (We will NOT attach the duplicate here to avoid double submissions.)

/* 
// Duplicate handler (COMMENTED OUT)
// document.getElementById("workerForm").addEventListener("submit", async function (e) {
//    ... (duplicated code) ...
// });
*/

// =======================================================
// LOGIN — UNIFIED, SAFE, SINGLE HANDLER
// - replaces multiple conflicting handlers present in the file.
// - handles admin (hardcoded), worker, graduate, NOT_VERIFIED.
// - uses the same php/login.php contract you provided.
// =======================================================
document.addEventListener("DOMContentLoaded", () => {

    // Ensure we do not accidentally bind multiple times
    const loginForm = document.getElementById("loginForm");
    if (!loginForm) return;

    // If a shared message box already exists, use it; else create
    let msgBox = document.getElementById("loginMessage");
    if (!msgBox) {
        msgBox = document.createElement("div");
        msgBox.id = "loginMessage";
        msgBox.className = "form-message";
        msgBox.style.display = "none";
        loginForm.parentNode.insertBefore(msgBox, loginForm);
    }

    // IMPORTANT: comment out legacy or conflicting login binders in the file.
    // The file previously had another login submit handler and an extra handleFormSubmit
    // call for '#loginForm'. Those are now effectively removed (commented) to avoid conflicts.
    // If you see lines like handleFormSubmit('#loginForm',...) elsewhere, keep them commented.

    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        // show loading state in the message box
        msgBox.style.display = "block";
        msgBox.classList.remove("error","success");
        msgBox.classList.add("loading");
        msgBox.innerHTML = "Logging in… <span class='spinner'></span>";
        msgBox.scrollIntoView({ behavior: "smooth", block: "center" });

        // disable submit button
        const submitBtn = loginForm.querySelector("button[type='submit']");
        const originalText = submitBtn ? submitBtn.innerText : null;
        if (submitBtn) {
            submitBtn.innerText = "Checking…";
            submitBtn.disabled = true;
        }

        // read inputs (safe)
        const emailEl = document.getElementById('login_email');
        const passEl  = document.getElementById('login_password');

        const email = emailEl ? emailEl.value.trim() : "";
        const password = passEl ? passEl.value : "";

        // role is chosen via radio name="login_role"
        const roleRadios = document.querySelectorAll('input[name="login_role"]');
        let role = "";
        roleRadios.forEach(r => { if (r.checked) role = r.value; });

        // Basic client validation
        if (!email || !password || !role) {
            msgBox.classList.remove("loading");
            msgBox.classList.add("error");
            msgBox.innerText = "Please enter email, password and select a role (Skilled Worker or Graduate).";
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = originalText; }
            return;
        }

        // --------------------------
        // ADMIN HARD-CODED CHECK
        // --------------------------
        // NOTE: old wrong admin credentials commented for history:
        // // if (email === "admin" && password === "admin") { ... }
        // Correct hardcode per your instruction:
        if (email === "naijaworkmen@gmail.com" && password === "admin123") {
            // Hide login panel and show admin dashboard
            document.querySelector(".login-signup-panel")?.classList.add("hidden");
            document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
            const admin = document.getElementById("admin-dashboard");
            if (admin) admin.classList.remove('hidden');

            // populate admin name safely
            const adminName = document.getElementById("admin-name");
            if (adminName) adminName.innerText = "Admin";

            // reset submit button
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = originalText; }
            msgBox.style.display = "none";
            return;
        }

        // --------------------------
        // Send AJAX to login.php
        // --------------------------
        try {
            const formData = new FormData();
            formData.append("email", email);
            formData.append("password", password);
            formData.append("login_role", role);

            const res = await fetch("php/login.php", { method: "POST", body: formData });
            const data = await res.json();

            // reset submit button and loading class
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = originalText; }
            msgBox.classList.remove("loading");

            // If server sent an error (including NOT_VERIFIED)
            if (data.status !== "success") {

                // Special handling for NOT_VERIFIED (your PHP uses code 'NOT_VERIFIED')
                if (data.code === "NOT_VERIFIED") {
                    msgBox.classList.add("error");
                    // show resend button inline
                    msgBox.innerHTML = `
                        ${data.message} <br>
                        <button id="resendBtnInline" class="mini-btn" style="margin-top:8px;">Resend Verification Email</button>
                    `;
                    // store pending email locally for reuse
                    if (data.email) localStorage.setItem("pending_email", data.email);

                    // attach handler for inline resend
                    const inlineBtn = document.getElementById("resendBtnInline");
                    if (inlineBtn) {
                        inlineBtn.onclick = async () => {
                            inlineBtn.disabled = true;
                            inlineBtn.innerText = "Sending…";
                            try {
                                const r = await fetch("php/resend_verification.php", {
                                    method: "POST",
                                    body: new URLSearchParams({ email: data.email })
                                });
                                const rr = await r.json();
                                msgBox.classList.remove("error");
                                msgBox.classList.add(rr.status === "success" ? "success" : "error");
                                msgBox.innerText = rr.message;
                            } catch (err) {
                                msgBox.classList.add("error");
                                msgBox.innerText = "Network error. Try again.";
                            } finally {
                                inlineBtn.disabled = false;
                                inlineBtn.innerText = "Resend Verification Email";
                            }
                        };
                    }
                    return;
                }

                // generic error
                msgBox.classList.add("error");
                msgBox.innerText = data.message || "Login failed";
                return;
            }

            // --------------------------
            // Successful login → route by role
            // --------------------------
            msgBox.classList.add("success");
            msgBox.innerText = data.message || "Login successful";

            // Hide login UI
            document.querySelector(".login-signup-panel")?.classList.add("hidden");
            document.getElementById("home")?.classList.add("hidden");

            // Route depending on role returned by PHP (should be 'worker' or 'graduate')
            const returnedRole = data.role || role;

            if (returnedRole === "worker") {
                // Show worker dashboard and populate safely
                document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
                const dash = document.getElementById("user-dashboard");
                if (dash) dash.classList.remove('hidden');

                safeSetText("user-name", data.full_name);
                safeSetText("profile-name", data.full_name);
                safeSetText("profile-email", data.email);
                safeSetText("profile-phone", data.phone);

                const pPic = document.getElementById("profile-pic");
                if (pPic) pPic.src = (data.photo && data.photo !== "") ? data.photo : "images/placeholder-passport.jpg";

                dash?.scrollIntoView({ behavior: "smooth" });
                return;
            }

            if (returnedRole === "graduate") {
                // Show graduate dashboard and populate safely
                document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
                const gDash = document.getElementById("graduate-dashboard");
                if (gDash) gDash.classList.remove('hidden');

                // populate graduate fields (IDs used based on HTML snippet)
                safeSetText("graduate-name", data.full_name || data.fullname || "");
                safeSetText("grad-profile-name", data.full_name || data.fullname || "");
                safeSetText("grad-profile-email", data.email || "");
                safeSetText("grad-profile-phone", data.phone || "");
                safeSetText("grad-highest-qualification", data.highest_qualification || "");
                safeSetText("grad-course", data.course || "");
                safeSetText("grad-institution", data.institution || "");
                safeSetText("grad-year", data.year_graduated || data.year_graduated || "");

                const gradPhoto = document.getElementById("grad-profile-pic") || document.getElementById("grad-pic");
                if (gradPhoto) gradPhoto.src = (data.photo && data.photo !== "") ? data.photo : "images/placeholder-passport.jpg";

                gDash?.scrollIntoView({ behavior: "smooth" });
                return;
            }

            // fallback — unknown role (shouldn't happen)
            console.warn("Login succeeded but role unknown:", data);
            msgBox.classList.remove("success");
            msgBox.classList.add("error");
            msgBox.innerText = "Login successful but role unknown. Contact admin.";

        } catch (err) {
            console.error("Login fetch error:", err);
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = originalText; }
            msgBox.classList.remove("loading");
            msgBox.classList.add("error");
            msgBox.innerText = "Network error. Please try again.";
        }
    });
});


// safe DOM text setter
function safeSetText(id, text) {
    const el = document.getElementById(id);
    if (!el) return;
    // support for <input> vs regular elements
    if ('value' in el && (el.tagName === "INPUT" || el.tagName === "TEXTAREA")) {
        el.value = text ?? "";
    } else {
        el.innerText = text ?? "";
    }
}


// =======================================================
// LEGACY CODE NOTES (left intentionally commented)
// - Earlier in this file you had another login submit block,
//   a roleButtons block looking for '.role-btn', and a
//   handleFormSubmit('#loginForm', ...) call. Those would
//   cause duplicate listeners or mismatch with your radio UI.
// - They are intentionally NOT active now (commented) so the
//   unified login flow above is the single source of truth.
// =======================================================

/* Example of legacy lines you may still see in the file:
handleFormSubmit('#loginForm', 'php/login.php', 'loginMessage');
document.getElementById('loginForm').addEventListener('submit', ... );
roleButtons.forEach(...);
*/
// If you want me to remove these leftover fragments permanently, I can — else they remain commented for traceability.


// =======================================================
// Graduate registration form handler binding (template)
// =======================================================
// We are using handleFormSubmit() for graduate registration below.
// If you prefer the custom code above, remove this call.
// (handleFormSubmit provided earlier in your file; if you kept it, it will be used.)
handleFormSubmit('#graduateForm', 'php/register_graduate.php', 'graduateMessage');


// =======================================================
// Placeholder assessment button (kept as-is)
// =======================================================
const assessBtn = document.getElementById('btnTakeAssessment');
if (assessBtn) {
    assessBtn.addEventListener('click', () => alert('Assessment module coming soon.'));
}
