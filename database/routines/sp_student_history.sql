CREATE PROCEDURE sp_student_history(IN p_student_id INT UNSIGNED)
BEGIN
    SELECT id, action, old_data, new_data, changed_at
    FROM student_audit
    WHERE student_id = p_student_id
    ORDER BY id;
END
