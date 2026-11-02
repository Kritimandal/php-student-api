CREATE PROCEDURE sp_count_students(
    IN p_term VARCHAR(100) CHARACTER SET utf8mb4,
    IN p_city VARCHAR(100) CHARACTER SET utf8mb4
)
BEGIN
    SELECT COUNT(*) AS total
    FROM students
    WHERE (p_term IS NULL OR name LIKE CONCAT('%', p_term, '%') COLLATE utf8mb4_unicode_ci ESCAPE '!' OR city LIKE CONCAT('%', p_term, '%') COLLATE utf8mb4_unicode_ci ESCAPE '!')
      AND (p_city IS NULL OR city = p_city COLLATE utf8mb4_unicode_ci);
END
