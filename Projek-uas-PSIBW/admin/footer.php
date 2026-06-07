<style>
    .custom-footer {
        background-color: #ffffff;
        border-top: 1px solid #e3e6f0;
        padding: 20px 0;
        margin-top: 30px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .footer-text {
        color: #858796;
        font-size: 14px;
        font-weight: 500;
        margin: 0;
    }
    .footer-brand {
        color: #4e73df;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .footer-brand:hover {
        color: #224abe;
    }
    .footer-heart {
        color: #e74a3b;
        animation: heartbeat 1.5s infinite;
        display: inline-block;
    }
    @keyframes heartbeat {
        0% { transform: scale(1); }
        50% { transform: scale(1.15); }
        100% { transform: scale(1); }
    }
</style>

<footer class="custom-footer sticky-footer w-100">
    <div class="container my-auto">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div class="text-center text-sm-start">
                <p class="footer-text">
                    &copy; <?= date('Y'); ?> <a href="#" class="footer-brand">SIAKAD Universitas Riau</a>. All Rights Reserved.
                </p>
            </div>
            <div class="text-center text-sm-end">
                <p class="footer-text">
                    Crafted with <span class="footer-heart"><i class="bi bi-heart-fill"></i></span> for UAS PSIBW
                </p>
            </div>
        </div>
    </div>
</footer>