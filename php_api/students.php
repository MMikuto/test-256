<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include __DIR__ . '/condb.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === "GET") {
        $stmt = $conn->prepare(
            "SELECT student_id, first_name AS firstName, last_name AS lastName, phone, email
             FROM students ORDER BY student_id DESC"
        );
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $result]);
    }

    // เพิ่มข้อมูลนักเรียน
    elseif ($method === "POST") {
        // ตรวจสอบว่าข้อมูลมาจาก JSON หรือ form-data
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

        if (stripos($contentType, "application/json") !== false) {
            $data = json_decode(file_get_contents("php://input"), true);
        } else {
            $data = $_POST;
        }

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ข้อมูลไม่ถูกต้อง"]);
            exit;
        }

        // ตรวจสอบค่าว่าง
        foreach (["firstName", "lastName", "phone", "email"] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === "") {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
                exit;
            }
        }

        if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "รูปแบบอีเมลไม่ถูกต้อง"]);
            exit;
        }

        // เพิ่มข้อมูลนักเรียน
        $stmt = $conn->prepare(
            "INSERT INTO students (first_name, last_name, phone, email)
             VALUES (:firstName, :lastName, :phone, :email)"
        );
        $stmt->execute([
            ":firstName" => trim($data["firstName"]),
            ":lastName" => trim($data["lastName"]),
            ":phone" => trim($data["phone"]),
            ":email" => trim($data["email"])
        ]);
        echo json_encode(["success" => true, "message" => "เพิ่มข้อมูลนักเรียนเรียบร้อย"]);
    }

    // ✅ แก้ไขข้อมูล
    elseif ($method === "PUT") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data) || !isset($data["student_id"])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ไม่พบค่า student_id"]);
            exit;
        }

        $studentIdValue = $data["student_id"];
        if (
            (!is_string($studentIdValue) && !is_int($studentIdValue)) ||
            !preg_match('/^[0-9]{1,8}$/D', (string) $studentIdValue) ||
            (int) $studentIdValue < 1
        ) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "รหัสนักเรียนไม่ถูกต้อง"]);
            exit;
        }
        $student_id = (int) $studentIdValue;

        foreach (["firstName", "lastName", "phone", "email"] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === "") {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
                exit;
            }
        }

        if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "รูปแบบอีเมลไม่ถูกต้อง"]);
            exit;
        }

        $stmt = $conn->prepare(
            "UPDATE students
             SET first_name = :firstName,
                 last_name = :lastName,
                 phone = :phone,
                 email = :email
             WHERE student_id = :id"
        );
        $stmt->execute([
            ":firstName" => trim($data["firstName"]),
            ":lastName" => trim($data["lastName"]),
            ":phone" => trim($data["phone"]),
            ":email" => trim($data["email"]),
            ":id" => $student_id
        ]);
        if ($stmt->rowCount() === 0) {
            $check = $conn->prepare("SELECT 1 FROM students WHERE student_id = :id");
            $check->execute([":id" => $student_id]);
            if (!$check->fetchColumn()) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลนักเรียน"]);
                exit;
            }
        }
        echo json_encode(["success" => true, "message" => "แก้ไขข้อมูลเรียบร้อย"]);
    }

    // ✅ ลบข้อมูล
    elseif ($method === "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data) || !isset($data["student_id"])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ไม่พบค่า student_id"]);
            exit;
        }

        $studentIdValue = $data["student_id"];
        if (
            (!is_string($studentIdValue) && !is_int($studentIdValue)) ||
            !preg_match('/^[0-9]{1,8}$/D', (string) $studentIdValue) ||
            (int) $studentIdValue < 1
        ) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "รหัสนักเรียนไม่ถูกต้อง"]);
            exit;
        }
        $student_id = (int) $studentIdValue;

        $stmt = $conn->prepare("DELETE FROM students WHERE student_id = :id");
        $stmt->bindParam(":id", $student_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลนักเรียน"]);
                exit;
            }
            echo json_encode(["success" => true, "message" => "ลบข้อมูลเรียบร้อย"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "ไม่สามารถลบข้อมูลนักเรียนได้"]);
        }
    }

    else {
        header("Allow: GET, POST, PUT, DELETE, OPTIONS");
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method ไม่ถูกต้อง"]);
    }

} catch (Exception $e) {
    error_log("Students API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการจัดการข้อมูลนักเรียน"]);
}
?>
