document.addEventListener("DOMContentLoaded", () => {
  const imageInput = document.getElementById("imageInput");
  const preview = document.getElementById("preview");
  const fileNameDiv = document.getElementById("fileName");
  const clearImageBtn = document.getElementById("clearImageBtn");
  const cropImageBtn = document.getElementById("cropImageBtn");
  const canvas = document.getElementById("croppedCanvas");
  const form = document.getElementById("leafForm");
  const loadingOverlay = document.getElementById("loadingOverlay");
  const modal = document.getElementById("resultModal");
  const modalClose = document.getElementById("modalClose");

  let cropper;
  loadingOverlay.style.display = "none";

  imageInput.addEventListener("change", () => {
    const file = imageInput.files[0];
    if (!file || !file.type.startsWith("image/")) {
      alert("Please upload a valid image.");
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = "block";
      fileNameDiv.textContent = "Selected: " + file.name;
      clearImageBtn.style.display = "inline-block";
      cropImageBtn.style.display = "inline-block";

      if (cropper) cropper.destroy();
      cropper = new Cropper(preview, {
        viewMode: 1,
        autoCropArea: 1,
      });
    };
    reader.readAsDataURL(file);
  });

  cropImageBtn.addEventListener("click", (e) => {
    e.preventDefault();
    if (!cropper) return;

    const croppedCanvas = cropper.getCroppedCanvas({
      width: 299,
      height: 299,
      imageSmoothingEnabled: true,
      imageSmoothingQuality: "high",
    });

    canvas.width = croppedCanvas.width;
    canvas.height = croppedCanvas.height;
    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(croppedCanvas, 0, 0);

    canvas.style.display = "block";
    preview.style.display = "none";
    cropper.destroy();
    cropper = null;

    canvas.toBlob((blob) => {
      const file = new File([blob], imageInput.files[0].name, {
        type: "image/png",
        lastModified: Date.now(),
      });

      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      imageInput.files = dataTransfer.files;
    }, "image/png");

    cropImageBtn.style.display = "none";
  });

  clearImageBtn.addEventListener("click", (e) => {
    e.preventDefault();
    imageInput.value = "";
    preview.style.display = "none";
    preview.src = "";
    fileNameDiv.textContent = "";
    canvas.style.display = "none";
    cropImageBtn.style.display = "none";
    clearImageBtn.style.display = "none";
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
  });

  form.addEventListener("submit", () => {
    loadingOverlay.style.display = "flex";
  });

  // Modal close handlers
  modalClose.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });
});
