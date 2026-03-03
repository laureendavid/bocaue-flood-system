// ===== MINI MAP =====
const map = L.map("mini-map", {
  zoomControl: false,
  dragging: false,
  scrollWheelZoom: false,
  doubleClickZoom: false,
  touchZoom: false,
  attributionControl: false,
}).setView([14.8, 120.905], 14);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);

let mapMarker = null;

const barangayCoords = {
  antipona: [14.8045, 120.901],
  bagumbayan: [14.802, 120.903],
  bambang: [14.7995, 120.905],
  batia: [14.797, 120.9045],
  binang1: [14.803, 120.9035],
  binang2: [14.801, 120.9055],
  bolacan: [14.805, 120.902],
  bundukan: [14.798, 120.906],
  bunlo: [14.7965, 120.903],
  caingin: [14.8035, 120.9075],
  duhat: [14.7955, 120.9015],
  igulot: [14.806, 120.904],
  lolomboy: [14.807, 120.9065],
  poblacion: [14.799, 120.9025],
  sulucan: [14.8, 120.908],
  taal: [14.7975, 120.907],
  tambobong: [14.7998, 120.9012],
  turo: [14.7985, 120.907],
  wakas: [14.806, 120.909],
};

function updateMap() {
  const val = document.getElementById("barangay").value;
  if (!val || !barangayCoords[val]) return;
  const coords = barangayCoords[val];
  map.flyTo(coords, 15, { duration: 0.8 });
  if (mapMarker) map.removeLayer(mapMarker);
  mapMarker = L.circleMarker(coords, {
    radius: 10,
    fillColor: "#dc2626",
    color: "#fff",
    weight: 2.5,
    fillOpacity: 0.92,
  }).addTo(map);
}

// ===== TOGGLE PASSWORD =====
function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector(".material-symbols-outlined");
  if (input.type === "password") {
    input.type = "text";
    icon.textContent = "visibility";
  } else {
    input.type = "password";
    icon.textContent = "visibility_off";
  }
}

// ===== PASSWORD STRENGTH =====
function updateStrength(val) {
  const bars = ["bar1", "bar2", "bar3", "bar4"];
  const colors = ["#ef4444", "#f59e0b", "#3b82f6", "#22c55e"];
  let score = 0;
  if (val.length >= 6) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  bars.forEach((id, i) => {
    document.getElementById(id).style.background =
      i < score ? colors[score - 1] : "#e2e8f0";
  });
}

// ===== STEP NAVIGATION =====
function showStep(n) {
  document
    .querySelectorAll(".step")
    .forEach((s) => s.classList.remove("active"));
  document.getElementById("step-" + n).classList.add("active");
  window.scrollTo(0, 0);
}

function goToSuccess() {
  // Submit form via AJAX or allow PHP form to redirect to step-2 (success)
  showStep(2);
}

// ===== FORM VALIDATION & SUBMIT =====
function handleRegister() {
  const firstName = document.getElementById("first-name").value.trim();
  const lastName = document.getElementById("last-name").value.trim();
  const email = document.getElementById("reg-email").value.trim();
  const password = document.getElementById("reg-password").value;
  const confirm = document.getElementById("reg-confirm").value;
  const contact = document.getElementById("contact").value.trim();
  const barangay = document.getElementById("barangay").value;
  const errorDiv = document.getElementById("reg-error");

  errorDiv.style.display = "none";

  if (
    !firstName ||
    !lastName ||
    !email ||
    !password ||
    !confirm ||
    !contact ||
    !barangay
  ) {
    showError("Please fill in all fields.");
    return;
  }

  if (password !== confirm) {
    showError("Passwords do not match.");
    return;
  }

  if (password.length < 6) {
    showError("Password must be at least 6 characters.");
    return;
  }

  // Submit the form
  document.getElementById("register-form").submit();
}

function showError(msg) {
  const errorDiv = document.getElementById("reg-error");
  errorDiv.querySelector("span.msg").textContent = msg;
  errorDiv.style.display = "flex";
}
