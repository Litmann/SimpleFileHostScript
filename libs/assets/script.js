// Sprachfunktion t() global bereitstellen
function t(key, replacements = {}) {
    const translations = window.translations || {};
    let text = translations[key] || key;

    for (const [k, v] of Object.entries(replacements)) {
        text = text.replace(`:${k}`, v);
    }
    return text;
}

document.addEventListener("DOMContentLoaded", function () {
    const dropArea = document.getElementById("drop-area");
    const fileInput = document.getElementById("files");
    const fileList = document.getElementById("file-list");
    let dt = new DataTransfer();

    // === Drag & Drop ===
    if (dropArea && fileInput) {
        dropArea.addEventListener("click", () => fileInput.click());

        dropArea.addEventListener("dragover", e => {
            e.preventDefault();
            dropArea.style.background = "rgba(255, 255, 255, 0.2)";
        });

        dropArea.addEventListener("dragleave", () => {
            dropArea.style.background = "transparent";
        });

        dropArea.addEventListener("drop", e => {
            e.preventDefault();
            dropArea.style.background = "transparent";
            for (let file of e.dataTransfer.files) dt.items.add(file);
            fileInput.files = dt.files;
            displayFileNames();
        });

        fileInput.addEventListener("change", () => {
            for (let file of fileInput.files) dt.items.add(file);
            fileInput.files = dt.files;
            displayFileNames();
        });

        // 📄 Dateinamen + Löschen-Buttons anzeigen
        function displayFileNames() {
            fileList.innerHTML = "";
            for (let i = 0; i < dt.files.length; i++) {
                fileList.innerHTML += `
                    <span>📄 ${dt.files[i].name}
                        <button class="btn btn-sm btn-danger remove-file" data-index="${i}">
                            ${t("upload_btn_remove")}
                        </button>
                    </span>`;
            }
            document.querySelectorAll(".remove-file").forEach(btn => {
                btn.onclick = () => removeFile(parseInt(btn.dataset.index));
            });
        }

        // 🗑️ Datei aus der Liste entfernen
        function removeFile(index) {
            let newDt = new DataTransfer();
            for (let i = 0; i < dt.files.length; i++) {
                if (i !== index) newDt.items.add(dt.files[i]);
            }
            dt = newDt;
            fileInput.files = dt.files;
            displayFileNames();
        }
    }

    // 📤 Datei-Upload starten
    window.uploadFiles = function () {
        const formData = new FormData();
        const errorMessage = document.getElementById("error-message");
        const linksContainer = document.getElementById("links");
        const progressBar = document.getElementById("progress-bar");

        if (fileInput.files.length === 0) {
            errorMessage.innerHTML = t("upload_error_nofile");
            return;
        }

        for (let file of fileInput.files) {
            if (file.size > maxFileSize * 1024 * 1024) {
                errorMessage.innerHTML = t("upload_error_toobig", {
                    file: file.name,
                    max: maxFileSize
                });
                return;
            }

            const fileExt = file.name.split('.').pop().toLowerCase();
            if (disallowedExtensions.includes(fileExt)) {
                errorMessage.innerHTML = t("upload_error_extension", {
                    ext: fileExt,
                    file: file.name
                });
                return;
            }
        }

        errorMessage.innerHTML = "";
        progressBar.style.display = "block";

        for (let file of fileInput.files) {
            formData.append("files[]", file);
        }

        const xhr = new XMLHttpRequest();
        xhr.open("POST", BASE_URL + "libs/files/upload.php", true);

        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                progressBar.value = Math.round((e.loaded / e.total) * 100);
            }
        };

        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                progressBar.style.display = "none";
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            linksContainer.innerHTML = `<h4>${t("upload_success_title")}</h4>`;
                            data.links.forEach(file => {
                                linksContainer.innerHTML += `
                                    <div class="download-section">
                                        <p><strong>${t("upload_label_file")}</strong> ${file.name}</p>
                                        <div class="input-group mb-2" style="max-width: 500px; margin: 0 auto;">
                                            <input type="text" class="form-control" value="${file.shortUrl}" readonly style="border-radius: 4px 0 0 4px; background: #222; color: #00c6ff;" align="center">
                                            <button class="btn btn-outline-secondary btn-sm copy-btn" type="button" style="border-radius: 0 4px 4px 0;" title="Link kopieren">${t("upload_btn_copy")}</button>
                                        </div>
                                        <p><strong>${t("upload_label_expiry")}</strong> ${file.expiry ? file.expiry : "Permanent"}</p>
                                        <a class="btn btn-success" href="${file.shortUrl}" target="_blank" style="margin-right: 10px;">${t("upload_btn_download")}</a>
                                        <a href="https://api.whatsapp.com/send?text=${encodeURIComponent("Hier ist dein Download: " + file.shortUrl)}" target="_blank" class="btn btn-outline-light" style="margin-right: 10px;">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" width="24" height="24" style="vertical-align: middle;"> ${t("upload_btn_whatsapp")}
                                        </a>
                                        <a href="mailto:?subject=Datei-Download&body=${encodeURIComponent("Hier ist dein Download: " + file.shortUrl)}" class="btn btn-outline-light">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/e/ec/Circle-icons-mail.svg" width="24" height="24" style="vertical-align: middle;"> ${t("upload_btn_email")}
                                        </a>
                                    </div>
                                `;
                            });

                            document.querySelectorAll('.copy-btn').forEach(btn => {
                                btn.addEventListener('click', function () {
                                    const input = this.parentElement.querySelector('input');
                                    input.select();
                                    document.execCommand('copy');
                                    this.innerText = t("upload_btn_copied");
                                    setTimeout(() => {
                                        this.innerText = t("upload_btn_copy");
                                    }, 2000);
                                });
                            });
                        } else {
                            linksContainer.innerHTML = "";
                            errorMessage.innerHTML = data.message || t("upload_error_generic");
                        }
                    } catch (err) {
                        errorMessage.innerHTML = t("upload_error_parse", { error: err });
                    }

                    dt = new DataTransfer();
                    fileInput.value = "";
                    if (fileList) fileList.innerHTML = "";
                    progressBar.value = 0;
                } else {
                    errorMessage.innerHTML = t("upload_error_status", { status: xhr.statusText });
                }
            }
        };

        xhr.send(formData);
    };

    // 🔍 Datei- und Benutzer-Suche
    const searchFilesEl = document.getElementById("searchFiles");
    if (searchFilesEl) {
        searchFilesEl.setAttribute("placeholder", t("search_files_placeholder"));
        searchFilesEl.onkeyup = () => {
            const query = searchFilesEl.value.toLowerCase().trim();
            const isUserSearch = query.startsWith("user:");
            const isFileSearch = query.startsWith("file:");
            let cleanQuery = query;
            if (isUserSearch) cleanQuery = query.replace("user:", "").trim();
            if (isFileSearch) cleanQuery = query.replace("file:", "").trim();

            document.querySelectorAll(".file-card").forEach(card => {
                const filename = card.dataset.filename?.toLowerCase() || "";
                const username = card.dataset.username?.toLowerCase() || "";
                let showCard = false;
                if (isUserSearch) {
                    showCard = username.includes(cleanQuery);
                } else if (isFileSearch) {
                    showCard = filename.includes(cleanQuery);
                } else {
                    showCard = filename.includes(cleanQuery) || username.includes(cleanQuery);
                }
                card.style.display = showCard ? "block" : "none";
            });
        };
    }

    const searchUsersEl = document.getElementById("searchUsers");
    if (searchUsersEl) {
        searchUsersEl.setAttribute("placeholder", t("search_users_placeholder"));
        searchUsersEl.onkeyup = () => {
            const query = searchUsersEl.value.toLowerCase();
            document.querySelectorAll("#userTable tbody tr").forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(query) ? "" : "none";
            });
        };
    }

    // ✅ Checkbox "Alle auswählen"
    const selectAllBtn = document.getElementById("selectAll");
    if (selectAllBtn) {
        selectAllBtn.innerText = t("admin_file_btn_all");
        selectAllBtn.onclick = () => {
            const boxes = document.querySelectorAll(".delete-checkbox");
            const checkAll = ![...boxes].every(box => box.checked);
            boxes.forEach(cb => cb.checked = checkAll);
        };
    }

    // 🗑️ Dateien löschen
    const deleteSelectedBtn = document.getElementById("deleteSelected");
    if (deleteSelectedBtn) {
        deleteSelectedBtn.innerText = t("admin_file_btn_delete");
        deleteSelectedBtn.onclick = () => {
            const ids = [...document.querySelectorAll(".delete-checkbox:checked")].map(cb => cb.value);
            if (!ids.length) {
                alert(t("delete_selected_none"));
                return;
            }
            if (!confirm(t("delete_selected_confirm"))) return;

            let params = new URLSearchParams();
            ids.forEach(id => params.append('ids[]', id));

            fetch(BASE_URL + "libs/files/delete_files.php?action=bulk", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: params.toString()
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert(data.message);
                })
                .catch(err => alert(t("delete_error_generic", { error: err })));
        };
    }

    document.querySelectorAll(".delete-file").forEach(btn => {
        btn.onclick = () => {
            if (!confirm(t("delete_single_confirm"))) return;
            const fileId = btn.dataset.id;
            fetch(`${BASE_URL}libs/files/delete_files.php?action=single&id=${fileId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) btn.closest(".file-card").remove();
                    else alert(data.message);
                })
                .catch(err => alert(t("delete_error_generic", { error: err })));
        };
    });

    // 🔐 Modal: Passwort zurücksetzen
    document.querySelectorAll('.reset-password-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const userId = this.getAttribute('data-id');
            const username = this.getAttribute('data-username');
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUsername').innerText = username;
            const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            modal.show();
        });
    });

    // 🔔 Session-Meldung automatisch ausblenden
    const sessionMsg = document.getElementById("sessionMsg");
    if (sessionMsg) {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(sessionMsg);
            bsAlert.close();
        }, 5000);
    }

    // 🧠 Aktiven Tab speichern (Admin-Dashboard)
    document.querySelectorAll('#dashboardTabs .list-group-item').forEach(tab => {
        tab.addEventListener('click', function () {
            localStorage.setItem('activeAdminTab', this.getAttribute('href'));
        });
    });

    const activeTab = localStorage.getItem('activeAdminTab');
    if (activeTab) {
        const triggerEl = document.querySelector(`#dashboardTabs a[href="${activeTab}"]`);
        if (triggerEl) {
            new bootstrap.Tab(triggerEl).show();
        }
    }
});
