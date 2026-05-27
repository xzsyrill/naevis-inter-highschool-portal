function qs(selector, root = document) {
  return root.querySelector(selector);
}
function qsa(selector, root = document) {
  return [...root.querySelectorAll(selector)];
}
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.add("open");
}
function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.remove("open");
}
function handleRankingChange(select) {
  if (select.value) {
    window.location.href = "teacher_dashboard.php?page=" + select.value;
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const slides = document.querySelectorAll(".hero-slide");
  const dots = document.querySelectorAll(".dot");

  if (!slides.length || !dots.length) return;

  let currentSlide = 0;
  let slideInterval;

  function showSlide(index) {
    slides.forEach((slide) => {
      slide.classList.remove("active");
    });

    dots.forEach((dot) => {
      dot.classList.remove("active");
    });

    slides[index].classList.add("active");
    dots[index].classList.add("active");

    currentSlide = index;
  }

  function nextSlide() {
    currentSlide++;

    if (currentSlide >= slides.length) {
      currentSlide = 0;
    }

    showSlide(currentSlide);
  }

  function startSlider() {
    slideInterval = setInterval(() => {
      nextSlide();
    }, 5000);
  }

  dots.forEach((dot, index) => {
    dot.addEventListener("click", () => {
      clearInterval(slideInterval);

      showSlide(index);

      startSlider();
    });
  });

  showSlide(0);
  startSlider();
});

function sanitizeGradeInput(input) {
  let value = input.value.replace(/[^0-9.]/g, "");
  const parts = value.split(".");
  if (parts.length > 2) value = parts[0] + "." + parts.slice(1).join("");
  if (value !== "") {
    let num = parseFloat(value);
    if (num > 100) num = 100;
    if (num < 0) num = 0;
    input.value = Number.isInteger(num) ? String(num) : String(num);
  }
}

function formatGrade(num) {
  if (num === null || num === "" || Number.isNaN(num)) return "";
  const rounded = Math.round(num * 100) / 100;
  return Number.isInteger(rounded)
    ? String(rounded)
    : rounded.toFixed(2).replace(/\.00$/, "");
}

function gradeRemark(grade) {
  if (grade === null || grade === "" || Number.isNaN(grade)) return "";
  return grade >= 75 ? "Passed" : "Failed";
}

function calculateFinals() {
  qsa("tr.grade-row").forEach((row) => {
    const inputs = qsa("input.grade-input", row);
    const filled = inputs.filter(
      (i) => i.value !== "" && !Number.isNaN(parseFloat(i.value)),
    );
    const finalBox = qs(".final-grade", row);
    const remarkBox = qs(".remarks", row);
    let finalValue = "";
    if (finalBox) {
      if (filled.length) {
        const total = filled.reduce((sum, i) => sum + parseFloat(i.value), 0);
        finalValue = Math.round((total / filled.length) * 100) / 100;
        finalBox.value = formatGrade(finalValue);
      } else {
        finalBox.value = "";
      }
    }
    if (remarkBox) remarkBox.value = gradeRemark(finalValue);
  });

  const finals = qsa(".final-grade").filter(
    (i) => i.value !== "" && !Number.isNaN(parseFloat(i.value)),
  );
  const gwa = qs("#general_average");
  const promo = qs("#promotion_status");
  if (gwa) {
    if (finals.length) {
      const total = finals.reduce((sum, i) => sum + parseFloat(i.value), 0);
      const avg = Math.round((total / finals.length) * 100) / 100;
      gwa.value = formatGrade(avg);
      if (promo) promo.value = avg >= 75 ? "Promoted" : "Retained";
    } else {
      gwa.value = "";
      if (promo) promo.value = "";
    }
  }
  syncReportCard();
}

function syncReportCard() {
  const printGwa = qs('[data-print="gwa"]');
  const printPromotion = qs('[data-print="promotion"]');
  const gwa = qs("#general_average");
  const promo = qs("#promotion_status");
  if (printGwa && gwa) printGwa.textContent = gwa.value || "-";
  if (printPromotion && promo) printPromotion.textContent = promo.value || "-";

  qsa("tr.grade-row").forEach((row) => {
    const subject = row.dataset.subject;
    if (!subject) return;
    const inputs = qsa("input.grade-input", row);
    inputs.forEach((input, index) => {
      const cell = qs(`[data-print="${subject}-q${index + 1}"]`);
      if (cell) cell.textContent = input.value || "-";
    });
    const finalBox = qs(".final-grade", row);
    const remarkBox = qs(".remarks", row);
    const finalCell = qs(`[data-print="${subject}-final"]`);
    const remarkCell = qs(`[data-print="${subject}-remarks"]`);
    if (finalCell)
      finalCell.textContent = finalBox && finalBox.value ? finalBox.value : "-";
    if (remarkCell)
      remarkCell.textContent =
        remarkBox && remarkBox.value ? remarkBox.value : "-";
  });
}

function printReportCard() {
  calculateFinals();
  window.print();
}

function resetAdminFilters() {
  window.location.href = "admin_dashboard.php";
}

document.addEventListener("input", (e) => {
  if (e.target.classList.contains("grade-input")) {
    sanitizeGradeInput(e.target);
    calculateFinals();
  }
});
document.addEventListener("change", (e) => {
  if (e.target.classList.contains("grade-input")) calculateFinals();
});
document.addEventListener("DOMContentLoaded", calculateFinals);

document.addEventListener("DOMContentLoaded", () => {
  const slides = qsa(".activity-slide");
  if (slides.length) {
    let current = 0;
    setInterval(() => {
      slides[current].classList.remove("active");
      current = (current + 1) % slides.length;
      slides[current].classList.add("active");
    }, 3500);
  }
});
