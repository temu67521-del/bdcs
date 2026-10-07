/* BDCS — Shared JS helpers */
const $  = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];
const esc = v => String(v ?? "").replace(/[&<>"']/g,
  c => ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c]));
const etb = n => Number(n || 0).toLocaleString("en-US") + " ETB";
const AVATAR = "https://ui-avatars.com/api/?background=0b3d91&color=fff&name=";

async function api(url, opt = {}) {
  if (url.startsWith("/api/")) url = "api.php?route=" + url;
  if (opt.body instanceof FormData) {
    if (opt.headers) delete opt.headers["Content-Type"];
  }
  const r = await fetch(url, opt);
  const text = await r.text();
  let d;
  try { d = JSON.parse(text); }
  catch (e) {
    console.error("Server response was not JSON:", text);
    throw new Error("Server error: " + text.substring(0, 120));
  }
  if (!r.ok) throw new Error(d.error || ("HTTP " + r.status));
  return d;
}

const head = `
<header>
  <a class="brand" href="index.html">🎓 Bahir Dar Computer School</a>
  <nav>
    <a href="index.html">Home</a>
    <a href="basic.html">Basic</a>
    <a href="programming.html">Programming</a>
    <a href="index.html#register">Register</a>
    <a href="status.html">Check Status</a>
    <a href="admin.html">Admin</a>
  </nav>
</header>`;

const footer = `
<footer>
  © Bahir Dar Computer School · Bahir Dar, Ethiopia ·
  <a href="admin.html">Admin</a>
</footer>`;

function renderHeadFoot() {
  document.body.insertAdjacentHTML("afterbegin", head);
  document.body.insertAdjacentHTML("beforeend", footer);
}

function fmtDate(s) {
  if (!s) return "—";
  const d = new Date(s);
  if (isNaN(d)) return s;
  return d.toLocaleDateString("en-GB", { day:"2-digit", month:"short", year:"numeric" });
}

function seats(c) {
  const maxE = Number(c.max_students) || 0;
  const enr  = Number(c.enrolled) || 0;
  const left = Math.max(maxE - enr, 0);
  const pct  = maxE > 0 ? Math.min(Math.round((enr / maxE) * 100), 100) : 0;
  const cls  = pct >= 100 ? "red" : pct >= 70 ? "yellow" : "green";
  return `<div class="quota">
    <b>${enr} / ${maxE} students</b> — ${left} seats left
    <div class="bar"><span class="${cls}" style="width:${pct}%"></span></div>
  </div>`;
}

function card(c) {
  const photo = c.photo_url || (AVATAR + encodeURIComponent(c.teacher_name || "Teacher"));
  const hasDisc = Number(c.discount) > 0;
  const finalPrice = Number(c.final_price || (c.fee - c.discount));
  const pct = c.fee > 0 ? Math.round((c.discount / c.fee) * 100) : 0;
  const dateLine = (c.start_date || c.end_date)
    ? fmtDate(c.start_date) + " → " + fmtDate(c.end_date)
    : c.duration;

  return `<article class="card" tabindex="0" data-cat="${esc(c.category)}">
    <div class="cardtop">
      <div class="cat-icon">${esc(c.category_icon || "📘")}</div>
      <span class="cid">#${c.id}</span>
    </div>
    <div class="badges">
      <span class="badge cat">${esc(c.category_name || "Course")}</span>
      <span class="badge lvl">${esc(c.level)}</span>
      ${hasDisc ? '<span class="badge disc">-' + pct + '% OFF</span>' : ""}
    </div>
    <h3>${esc(c.name)}</h3>
    <div class="price">
      <span class="now">${etb(finalPrice)}</span>
      ${hasDisc ? '<span class="was">' + etb(c.fee) + '</span>' : ""}
    </div>
    <div class="dates">
      <div class="row"><span>📅 Duration</span><b>${esc(c.duration)}</b></div>
      <div class="row"><span>🗓 Dates</span><b>${esc(dateLine)}</b></div>
      <div class="row"><span>⏰ Class</span><b>${esc(c.schedule || "—")}</b></div>
    </div>
    ${seats(c)}
    <div class="teacher-mini">
      <img src="${esc(photo)}" alt="">
      <div><small>Teacher</small><br><b>${esc(c.teacher_name || "TBA")}</b></div>
    </div>
    <div class="desc">${esc(c.description || "")}</div>
    <div class="actions">
      <a class="btn" href="course.html?id=${c.id}">Details</a>
      <a class="btn gold" href="index.html?course=${c.id}#register">Enroll Now</a>
    </div>
  </article>`;
}
