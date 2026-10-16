<template>
  <div class="container mt-4">
    <h2 class="mb-3">รายชื่อนักเรียน</h2>
    
    <div class="mb-3">
      <button class="btn btn-primary" @click="openAddModal">
        Add+ <i class="bi bi-plus-circle"></i>
      </button>
    </div>

    <table class="table table-bordered table-striped">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>ชื่อ</th>
          <th>นามสกุล</th>
          <th>เบอร์โทร</th>
          <th>อีเมล</th>
          <th>แก้ไข/ลบ</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="student in students" :key="student.student_id">
          <td>{{ student.student_id }}</td>
          <td>{{ student.firstName }}</td>
          <td>{{ student.lastName }}</td>
          <td>{{ student.phone }}</td>
          <td>{{ student.email }}</td>
          <td>
            <button class="btn btn-warning btn-sm" @click="openEditModal(student)">
              แก้ไข
            </button>
            |
            <button class="btn btn-danger btn-sm" @click="deleteStudent(student.student_id)">
              ลบ
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="loading" class="text-center"><p>กำลังโหลดข้อมูล...</p></div>
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- ✅ Modal ใช้ทั้งเพิ่ม/แก้ไข -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditMode ? "แก้ไขข้อมูลนักเรียน" : "เพิ่มนักเรียนใหม่" }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveStudent">
              <div class="mb-3">
                <label class="form-label">ชื่อ</label>
                <input v-model="editStudent.firstName" type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">นามสกุล</label>
                <input v-model="editStudent.lastName" type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">เบอร์โทร</label>
                <input v-model="editStudent.phone" type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">อีเมล</label>
                <input v-model="editStudent.email" type="email" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-success">
                {{ isEditMode ? "บันทึกการแก้ไข" : "เพิ่มนักเรียน" }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { Modal } from "bootstrap";

export default {
  name: "StudentList",
  setup() {
    const students = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const editStudent = ref({});
    const isEditMode = ref(false);
    let editModal = null;
    const apiUrl = "http://localhost/test-256/php_api/students.php";

    const fetchStudents = async () => {
      try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
          throw new Error("ไม่สามารถโหลดข้อมูลนักเรียนได้");
        }
        const result = await response.json();

        if (result.success && Array.isArray(result.data)) {
          students.value = result.data;
        } else {
          throw new Error(result.message || "รูปแบบข้อมูลนักเรียนไม่ถูกต้อง");
        }
      } catch (err) {
        error.value = err.message;
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      fetchStudents();
      const modalEl = document.getElementById("editModal");
      if (modalEl) {
        editModal = new Modal(modalEl);
      }
    });

    // เปิด Modal เพิ่มนักเรียนใหม่
    const openAddModal = () => {
      isEditMode.value = false;
      editStudent.value = {
        firstName: "",
        lastName: "",
        phone: "",
        email: ""
      };
      editModal?.show();
    };

    // เปิด Modal แก้ไขนักเรียน
    const openEditModal = (student) => {
      isEditMode.value = true;
      editStudent.value = { ...student };
      editModal?.show();
    };

    // ใช้ฟังก์ชันเดียวสำหรับทั้งเพิ่ม/แก้ไข
    const saveStudent = async () => {
      const method = isEditMode.value ? "PUT" : "POST";

      try {
        const response = await fetch(apiUrl, {
          method,
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(editStudent.value)
        });

        const result = await response.json();

        if (response.ok && result.success) {
          alert(result.message);
          await fetchStudents();
          editModal?.hide();
        } else {
          alert(result.message || "ไม่สามารถบันทึกข้อมูลนักเรียนได้");
        }
      } catch (err) {
        alert("เกิดข้อผิดพลาด: " + err.message);
      }
    };

    // ลบนักเรียน
    const deleteStudent = async (id) => {
      if (!confirm("คุณต้องการลบข้อมูลนี้ใช่หรือไม่?")) return;
      try {
        const response = await fetch(apiUrl, {
          method: "DELETE",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({student_id: id })
        });
        const result = await response.json();
        if (response.ok && result.success) {
          students.value = students.value.filter(student => Number(student.student_id) !== Number(id));
          alert(result.message);
        } else {
          alert(result.message || "ไม่สามารถลบข้อมูลนักเรียนได้");
        }
      } catch (err) {
        alert("เกิดข้อผิดพลาด: " + err.message);
      }
    };

    return {
      students,
      loading,
      error,
      editStudent,
      isEditMode,
      openAddModal,
      openEditModal,
      saveStudent,
      deleteStudent
    };
  }
};
</script>