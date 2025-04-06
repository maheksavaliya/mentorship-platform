<!-- Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Appointment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="appointment-details">
                    <p><strong>Mentee:</strong> <span id="mentee_name"></span></p>
                    <p><strong>Date:</strong> <span id="appointment_date"></span></p>
                    <p><strong>Time:</strong> <span id="appointment_time"></span></p>
                    <p><strong>Purpose:</strong> <span id="appointment_purpose"></span></p>
                </div>
                <form id="appointmentForm" action="update_appointment.php" method="POST">
                    <input type="hidden" name="appointment_id" id="appointment_id">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accept</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div> 