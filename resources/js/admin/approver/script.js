

// Delete confirmation functionality
let approverIdToDelete = null;

// Initialize delete functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeDeleteFunctionality();
});

function initializeDeleteFunctionality() {
    // Add event listeners to all delete buttons
    const deleteButtons = document.querySelectorAll('.delete-approver-btn');
    const cancelButtons = document.querySelectorAll('#cancelDeleteButton');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const approverId = this.getAttribute('data-approver-id');
            const approverName = this.getAttribute('data-approver-name');
            confirmDelete(approverId, approverName);
        });
    });

    // Add event listener for modal close button
    const closeModalBtn = document.querySelector('#deleteConfirmModal .bg-gray-500');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeDeleteModal);
    }

    // Add event listener for confirm delete button
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', executeDelete);
    }

    // Add event listener for cancel buttons
    cancelButtons.forEach(button => {
        button.addEventListener('click', closeDeleteModal);
    });
}

function confirmDelete(approverId, approverName) {
    approverIdToDelete = approverId;
    document.getElementById('approverName').textContent = approverName;
    document.getElementById('deleteConfirmModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeDeleteModal() {
    approverIdToDelete = null;
    document.getElementById('deleteConfirmModal').classList.add('hidden');
    document.body.style.overflow = 'auto'; // Restore scrolling
}

function executeDelete() {
    if (!approverIdToDelete) return;

    // Show loading state
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteText = document.getElementById('deleteButtonText');
    const deleteSpinner = document.getElementById('deleteSpinner');

    const cancelButtons = document.querySelectorAll('#cancelDeleteButton');
    cancelButtons.forEach(btn => {
        btn.disabled = true;
    });

    deleteBtn.disabled = true;
    deleteText.textContent = 'Deleting...';
    deleteSpinner.classList.remove('hidden');

    // Submit the form
    document.getElementById(`delete-form-${approverIdToDelete}`).submit();
}

// Close modal when pressing Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
    }
});
