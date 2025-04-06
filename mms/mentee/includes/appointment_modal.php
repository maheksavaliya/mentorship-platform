<?php
if (!isset($_SESSION['mentee_id'])) {
    header("Location: login");
    exit();
}
?>

<!-- Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white" style="background: linear-gradient(135deg, #6a11cb, #2575fc);">
                <h5 class="modal-title text-white" id="appointmentModalLabel">Book a Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="appointmentForm" action="book_session.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="mentor_id" id="mentor_id">
                    
                    <div class="mb-3">
                        <label for="session_title" class="form-label">Session Title</label>
                        <input type="text" class="form-control" id="session_title" name="title" required
                               placeholder="e.g., Career Guidance Session">
                    </div>

                    <div class="mb-3">
                        <label for="session_description" class="form-label">What would you like to discuss?</label>
                        <textarea class="form-control" id="session_description" name="description" rows="3" required
                                  placeholder="Briefly describe what you'd like to discuss in this session..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="session_date" class="form-label">Preferred Date</label>
                            <input type="date" class="form-control" id="session_date" name="date" required
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="session_time" class="form-label">Preferred Time</label>
                            <input type="time" class="form-control" id="session_time" name="time" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="session_duration" class="form-label">Duration (minutes)</label>
                        <select class="form-select" id="session_duration" name="duration" required>
                            <option value="30">30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60" selected>1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #11998e, #38ef7d); border: none;">
                        <i class="fas fa-calendar-check me-2"></i>Book Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Basic validation
    const title = document.getElementById('session_title').value.trim();
    const description = document.getElementById('session_description').value.trim();
    const date = document.getElementById('session_date').value;
    const time = document.getElementById('session_time').value;
    
    if (!title || !description || !date || !time) {
        alert('Please fill in all required fields');
        return;
    }
    
    // Submit form
    this.submit();
});

// Set minimum date to today
document.getElementById('session_date').min = new Date().toISOString().split('T')[0];
</script> 