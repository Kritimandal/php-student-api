CREATE PROCEDURE sp_count_students(
    IN p_term VARCHAR(100),
    IN p_city VARCHAR(100)
)
BEGIN
    SELECT COUNT(*) AS total
    FROM students
    WHERE (p_term IS NULL OR name LIKE CONCAT('%', p_term, '%') ESCAPE '!' OR city LIKE CONCAT('%', p_term, '%') ESCAPE '!')
      AND (p_city IS NULL OR city = p_city);
END
