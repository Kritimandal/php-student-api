CREATE TRIGGER trg_students_after_update
AFTER UPDATE ON students
FOR EACH ROW
INSERT INTO student_audit (student_id, action, old_data, new_data)
VALUES (
    NEW.id,
    'update',
    JSON_OBJECT('name', OLD.name, 'age', OLD.age, 'city', OLD.city),
    JSON_OBJECT('name', NEW.name, 'age', NEW.age, 'city', NEW.city)
)
