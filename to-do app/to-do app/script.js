document.addEventListener("DOMContentLoaded", function() {
    // ✅ Registration form logic (KEEP THIS)
    const registerForm = document.querySelector("#registerForm");
    if (registerForm) {
        registerForm.addEventListener("submit", function(event) {
            event.preventDefault();

            const formData = new FormData(registerForm);

            fetch("register.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes("რეგისტრაცია წარმატებულია")) {
                    window.location.href = "login.php";
                }
            })
            .catch(error => console.error("შეცდომა:", error));
        });
    }
    document.addEventListener("DOMContentLoaded", function() {
        // Confirm before deleting a task
        document.querySelectorAll(".delete-task").forEach(button => {
            button.addEventListener("click", function(event) {
                if (!confirm("ნამდვილად გსურთ ამ გეგმის წაშლა?")) {
                    event.preventDefault();
                }
            });
        });
    
        // Prefill the form for editing a task
        document.querySelectorAll(".edit-task").forEach(button => {
            button.addEventListener("click", function() {
                const task = JSON.parse(this.dataset.task);
                document.getElementById("task_id").value = task.id;
                document.querySelector("input[name='date']").value = task.date;
                document.querySelector("input[name='title']").value = task.title;
                document.querySelector("textarea[name='description']").value = task.description;
                document.querySelector("select[name='status']").value = task.status;
                document.querySelector("select[name='priority']").value = task.priority;
            });
        });
    });
    

   
});


