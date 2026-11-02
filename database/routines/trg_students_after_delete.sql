CREATE TRIGGER trg_students_after_delete
AFTER DELETE ON students
FOR EACH ROW
INSERT INTO student_audit (student_id, action, old_data, new_data)
VALUES (
    OLD.id,
    'delete',
    JSON_OBJECT('name', OLD.name, 'age', OLD.age, 'city', OLD.city),
    NULL
)
