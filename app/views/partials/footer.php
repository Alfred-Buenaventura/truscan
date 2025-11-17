</main> 
    </div> 

    <div id="logoutConfirmModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h3><i class="fa-solid fa-arrow-right-from-bracket"></i> Confirm Logout</h3>
                <button type="button" class="modal-close" onclick="closeModal('logoutConfirmModal')">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p style="font-size: 1rem; color: var(--gray-700);">Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('logoutConfirmModal')">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="window.location.href='logout.php'">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Log Out
                </button>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>