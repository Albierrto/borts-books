<?php
// Edit Form (Hidden by Default)
?>
<div id="edit-form-<?php echo $submission['id']; ?>" 
     style="display: none; margin-top: 15px; padding: 15px; 
            background: <?php echo $statusStyle['bg']; ?>; 
            border: 1px solid <?php echo $statusStyle['border']; ?>;
            border-radius: 6px;">
    <form method="POST" class="update-form" style="display: grid; gap: 15px;">
        <input type="hidden" name="action" value="update_submission">
        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; color: <?php echo $statusStyle['text']; ?>;">
                    Status
                </label>
                <select name="status" required 
                        style="width: 100%; padding: 8px; 
                               background: white;
                               border: 1px solid <?php echo $statusStyle['border']; ?>; 
                               border-radius: 6px;">
                    <option value="pending" <?php echo $submission['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $submission['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="quoted" <?php echo $submission['status'] === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                    <option value="completed" <?php echo $submission['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo $submission['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500; color: <?php echo $statusStyle['text']; ?>;">
                    Quote Amount ($)
                </label>
                <input type="number" name="quote_amount" step="0.01" min="0" 
                       value="<?php echo $submission['quote_amount'] ?? ''; ?>"
                       style="width: 100%; padding: 8px; 
                              background: white;
                              border: 1px solid <?php echo $statusStyle['border']; ?>; 
                              border-radius: 6px;">
            </div>
        </div>

        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: 500; color: <?php echo $statusStyle['text']; ?>;">
                Admin Notes
            </label>
            <textarea name="admin_notes" rows="3" 
                      style="width: 100%; padding: 8px; 
                             background: white;
                             border: 1px solid <?php echo $statusStyle['border']; ?>; 
                             border-radius: 6px; resize: vertical;"
            ><?php echo htmlspecialchars($submission['admin_notes'] ?? ''); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" onclick="toggleEdit(<?php echo $submission['id']; ?>)"
                    style="padding: 8px 16px; background: #6b7280; color: white; 
                           border: none; border-radius: 6px; cursor: pointer;">
                Cancel
            </button>
            <button type="submit" 
                    style="padding: 8px 16px; 
                           background: <?php echo $statusStyle['border']; ?>; 
                           color: white; border: none; border-radius: 6px; cursor: pointer;">
                Save Changes
            </button>
        </div>
    </form>
</div> 