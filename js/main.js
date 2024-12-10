document.addEventListener("DOMContentLoaded", () => {
  const adminBtn = document.getElementById("adminBtn");
  const staffBtn = document.getElementById("staffBtn");
  const studentBtn = document.getElementById("studentBtn");

  const confirmAdmin = document.getElementById("confirmAdmin");
  const adminMessage = document.getElementById("adminMessage");

  adminBtn.addEventListener("click", () => {
      const adminModal = new bootstrap.Modal(document.getElementById("adminModal"));
      adminModal.show();
      adminMessage.innerHTML = "";
      adminMessage.className = "";
  });

  confirmAdmin.addEventListener("click", () => {
      const passwordValue = document.getElementById("adminPassword").value;

      let post = {
        password: passwordValue
      };

      fetch("php-api/AccessAdmin.php", {
          method: 'post',
          body: JSON.stringify(post),
          headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json'
          }
      }).then((response) => {
          return response.json()
      }).then((res) => {
          if (res.status === 'success') {
              console.log(res.message);
              adminMessage.innerHTML = "Successful!. Redirecting...";
              adminMessage.className = "text-success mt-1";
              setTimeout(() => {
                window.location.href = "admin/dashboard.php";
            }, 1500);
          } else if(res.status == 'failed') {
            console.log(res.message + ' pass: ' + post.password);
            adminMessage.innerHTML = "Incorrect password. Please try again.";
            adminMessage.className = "text-danger";
          }
      }).catch((error) => {
          console.log(error)
      });
  });

  staffBtn.addEventListener("click", () => {
    window.location.href = "staff/staffIndex.php";
  });

  studentBtn.addEventListener("click", () => {
      window.location.href = "student/studentIndex.php";
  });
});
