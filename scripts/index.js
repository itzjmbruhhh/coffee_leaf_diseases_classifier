document.addEventListener("DOMContentLoaded", function () {
  const imageInput = document.getElementById("imageInput");
  const preview = document.getElementById("preview");
  const fileNameDiv = document.getElementById("fileName");
  const clearImageBtn = document.getElementById("clearImageBtn");
  const modal = document.getElementById("resultModal");
  const modalClose = document.getElementById("modalClose");
  const form = document.getElementById("leafForm");
  const loadingOverlay = document.getElementById("loadingOverlay");

  // Ensure overlay is hidden on load
  loadingOverlay.style.display = "none";

  form.addEventListener("submit", function () {
    loadingOverlay.style.display = "flex";
  });

  function handleFile(file) {
    if (!file.type.startsWith("image/")) {
      alert("Please upload a valid image file.");
      return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = "block";
      fileNameDiv.textContent = file.name;
      clearImageBtn.style.display = "inline-block";
    };
    reader.readAsDataURL(file);
  }

  imageInput.addEventListener("change", function () {
    const file = this.files[0];
    if (file) {
      handleFile(file);
    }
  });

  clearImageBtn.addEventListener("click", function (e) {
    e.preventDefault();
    imageInput.value = "";
    preview.src = "#";
    preview.style.display = "none";
    fileNameDiv.textContent = "";
    clearImageBtn.style.display = "none";
  });

  modalClose.addEventListener("click", function () {
    modal.style.display = "none";
  });

  window.addEventListener("click", function (e) {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });

  if (window.__SHOW_MODAL__) {
    modal.style.display = "block";
  }
});