<?php
include '../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }

$stats = [
  'users'    => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
  'buses'    => $pdo->query("SELECT COUNT(*) FROM buses")->fetchColumn(),
  'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
  'revenue'  => $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE payment_status='paid'")->fetchColumn(),
  'paid'     => $pdo->query("SELECT COUNT(*) FROM bookings WHERE payment_status='paid'")->fetchColumn(),
  'pending'  => $pdo->query("SELECT COUNT(*) FROM bookings WHERE payment_status='pending'")->fetchColumn(),
];
$recent = $pdo->query("SELECT b.*,u.username,bu.bus_name,bu.from_city,bu.to_city FROM bookings b JOIN users u ON b.user_id=u.id JOIN buses bu ON b.bus_id=bu.id ORDER BY b.booking_date DESC LIMIT 10")->fetchAll();
$buses  = $pdo->query("SELECT b.*,COUNT(CASE WHEN s.status='available' THEN 1 END) AS avail_seats,COUNT(s.id) AS seat_count FROM buses b LEFT JOIN seats s ON b.id=s.bus_id GROUP BY b.id ORDER BY b.travel_date DESC")->fetchAll();
$users  = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
$all_bk = $pdo->query("SELECT b.*,u.username,bu.bus_name,bu.from_city,bu.to_city FROM bookings b JOIN users u ON b.user_id=u.id JOIN buses bu ON b.bus_id=bu.id ORDER BY b.id DESC")->fetchAll();

// AJAX
if (isset($_GET['action'])) {
  header('Content-Type: application/json');
  if ($_GET['action']==='delete_bus') {
    $pdo->prepare("DELETE FROM buses WHERE id=?")->execute([$_GET['id']]);
    echo json_encode(['success'=>true]); exit;
  }
  if ($_GET['action']==='cancel_booking') {
    $bk=$pdo->prepare("SELECT * FROM bookings WHERE id=?"); $bk->execute([$_GET['id']]); $bk=$bk->fetch();
    if($bk){ $seats=explode(',',$bk['seat_numbers']); $u=$pdo->prepare("UPDATE seats SET status='available' WHERE bus_id=? AND seat_number=?"); foreach($seats as $s) $u->execute([$bk['bus_id'],trim($s)]); $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([$_GET['id']]); }
    echo json_encode(['success'=>true]); exit;
  }
  if ($_GET['action']==='delete_user') {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_GET['id']]);
    echo json_encode(['success'=>true]); exit;
  }
}
// Add bus
if ($_POST && isset($_POST['add_bus'])) {
  $s=$pdo->prepare("INSERT INTO buses(bus_name,from_city,to_city,travel_date,total_seats,price_per_seat) VALUES(?,?,?,?,?,?)");
  $s->execute([$_POST['bus_name'],$_POST['from_city'],$_POST['to_city'],$_POST['travel_date'],$_POST['total_seats'],$_POST['price_per_seat']]);
  $bid=$pdo->lastInsertId();
  $ins=$pdo->prepare("INSERT INTO seats(bus_id,seat_number) VALUES(?,?)");
  for($i=1;$i<=$_POST['total_seats'];$i++) $ins->execute([$bid,$i]);
  header('Location: dashboard.php?sec=buses'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BusGo — Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--bg:#0a0f1e;--surface:#111827;--card:#1a2235;--border:#1f2d45;--accent:#00d4aa;--accent2:#6c63ff;--red:#ff4d6d;--yellow:#fbbf24;--orange:#f97316;--text:#e2e8f0;--muted:#64748b;--sw:260px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}
.sidebar{width:var(--sw);background:var(--surface);position:fixed;top:0;left:0;height:100vh;display:flex;flex-direction:column;border-right:1px solid var(--border);z-index:100}
.logo{padding:26px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.logo-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--accent2),var(--red));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.logo h2{font-family:'Syne',sans-serif;font-size:21px;font-weight:800;background:linear-gradient(135deg,var(--accent2),var(--red));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo small{font-size:11px;color:var(--muted);display:block;margin-top:1px}
.user-box{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.av{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--red),var(--accent2));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:17px;color:#fff;flex-shrink:0}
.av-name{font-weight:600;font-size:14px}
.av-role{font-size:11px;color:var(--red);background:rgba(255,77,109,.12);padding:2px 10px;border-radius:20px;display:inline-block;margin-top:4px}
nav{flex:1;padding:14px 0;overflow-y:auto}
.ni{display:flex;align-items:center;gap:13px;padding:13px 22px;cursor:pointer;color:var(--muted);font-size:14px;font-weight:500;border-left:3px solid transparent;transition:.2s}
.ni:hover{color:var(--text);background:rgba(255,255,255,.03)}
.ni.active{color:var(--accent2);background:rgba(108,99,255,.07);border-left-color:var(--accent2)}
.ni i{width:17px;text-align:center}
.sf{padding:18px 22px;border-top:1px solid var(--border)}
.lg-btn{display:flex;align-items:center;gap:10px;color:var(--red);font-size:14px;text-decoration:none;padding:10px 14px;border-radius:10px;transition:.2s}
.lg-btn:hover{background:rgba(255,77,109,.1)}
/* MAIN */
.main{margin-left:var(--sw);flex:1;padding:30px;background:var(--bg)}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
.pg-title{font-family:'Syne',sans-serif;font-size:24px;font-weight:800}
.pg-title span{color:var(--accent2)}
.admin-pill{background:var(--card);border:1px solid var(--border);padding:7px 16px;border-radius:30px;font-size:12px;color:var(--muted);display:flex;align-items:center;gap:7px}
.rdot{width:7px;height:7px;background:var(--red);border-radius:50%;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.5);opacity:.5}}
/* SECTIONS */
.sec{display:none;animation:fu .35s ease}
.sec.active{display:block}
@keyframes fu{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
/* STATS */
.sg{display:grid;grid-template-columns:repeat(auto-fit,minmax(185px,1fr));gap:18px;margin-bottom:28px}
.sc{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:22px;position:relative;overflow:hidden;transition:.3s}
.sc:hover{transform:translateY(-3px)}
.sc::before{content:'';position:absolute;top:-25px;right:-25px;width:80px;height:80px;border-radius:50%;opacity:.08}
.sc.g::before{background:var(--accent)}.sc.p::before{background:var(--accent2)}.sc.y::before{background:var(--yellow)}.sc.r::before{background:var(--red)}.sc.o::before{background:var(--orange)}
.si{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:17px;margin-bottom:14px}
.sc.g .si{background:rgba(0,212,170,.12);color:var(--accent)}.sc.p .si{background:rgba(108,99,255,.12);color:var(--accent2)}.sc.y .si{background:rgba(251,191,36,.12);color:var(--yellow)}.sc.r .si{background:rgba(255,77,109,.12);color:var(--red)}.sc.o .si{background:rgba(249,115,22,.12);color:var(--orange)}
.sv{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;margin-bottom:3px}
.sl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
/* TABLE */
.tw{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;overflow-x:auto}
.tbl{width:100%;border-collapse:collapse;min-width:600px}
.tbl thead tr{background:rgba(255,255,255,.02)}
.tbl th{padding:13px 16px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border)}
.tbl td{padding:13px 16px;font-size:13px;border-bottom:1px solid rgba(31,45,69,.4);vertical-align:middle}
.tbl tbody tr:hover{background:rgba(255,255,255,.02)}
.tbl tbody tr:last-child td{border:none}
.badge{padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.badge.paid{background:rgba(0,212,170,.12);color:var(--accent)}.badge.pending{background:rgba(251,191,36,.12);color:var(--yellow)}.badge.failed{background:rgba(255,77,109,.12);color:var(--red)}
/* BUTTONS */
.dbtn{background:rgba(255,77,109,.12);border:1px solid rgba(255,77,109,.3);color:var(--red);padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:.2s;font-family:'DM Sans',sans-serif}
.dbtn:hover{background:var(--red);color:#fff}
.abtn{background:rgba(0,212,170,.12);border:1px solid rgba(0,212,170,.3);color:var(--accent);padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:.2s;font-family:'DM Sans',sans-serif}
.abtn:hover{background:var(--accent);color:#0a0f1e}
/* SECTION HEADER */
.sh{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
.sec-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:700}
.sec-title span{color:var(--accent2)}
/* ADD BUS FORM */
.form-panel{background:var(--bg);border:1px solid var(--border);border-radius:14px;padding:24px;margin-bottom:20px;display:none}
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fg{margin-bottom:0}
.fg label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px}
.fg input{width:100%;background:var(--card);border:1px solid var(--border);color:var(--text);padding:11px 14px;border-radius:10px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;transition:.2s}
.fg input:focus{border-color:var(--accent2);box-shadow:0 0 0 3px rgba(108,99,255,.1)}
.form-actions{display:flex;gap:10px;margin-top:16px}
.sbmt{background:linear-gradient(135deg,var(--accent2),var(--accent));border:none;color:#fff;padding:12px 28px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Syne',sans-serif}
.cncl{background:rgba(255,255,255,.05);border:1px solid var(--border);color:var(--muted);padding:12px 20px;border-radius:10px;font-size:14px;cursor:pointer}
/* AVAIL BAR */
.abar{height:5px;background:var(--border);border-radius:3px;margin-top:4px;overflow:hidden;width:70px}
.afill{height:100%;border-radius:3px}
/* MINI STATS ROW */
.msr{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:28px}
.ms{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center}
.ms-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800}
.ms-lbl{font-size:11px;color:var(--muted);margin-top:3px}
/* SCROLLBAR */
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
/* TOAST */
.toast{position:fixed;bottom:22px;right:22px;background:var(--card);border:1px solid var(--accent2);border-radius:12px;padding:14px 22px;font-size:13px;font-weight:500;z-index:9999;transform:translateY(80px);opacity:0;transition:.4s}
.toast.show{transform:none;opacity:1}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">
    <div class="logo-icon">🛠</div>
    <div><h2>BusGo</h2><small>Admin Panel</small></div>
  </div>
  <div class="user-box">
    <div class="av"><?php echo strtoupper(substr($_SESSION['admin_username'],0,1)); ?></div>
    <div>
      <div class="av-name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
      <span class="av-role">Administrator</span>
    </div>
  </div>
  <nav>
    <div class="ni active" onclick="show('dashboard',this)"><i class="fas fa-chart-pie"></i> Dashboard</div>
    <div class="ni" onclick="show('buses',this)"><i class="fas fa-bus"></i> Manage Buses</div>
    <div class="ni" onclick="show('bookings',this)"><i class="fas fa-ticket-alt"></i> All Bookings</div>
    <div class="ni" onclick="show('users',this)"><i class="fas fa-users"></i> Users</div>
  </nav>
  <div class="sf">
    <a href="admin_logout.php" class="lg-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="pg-title" id="pgTitle">Dashboard <span>Overview</span></div>
    <div class="admin-pill"><div class="rdot"></div> Admin Mode</div>
  </div>

  <!-- DASHBOARD -->
  <section id="dashboard" class="sec active">
    <div class="sg">
      <div class="sc g"><div class="si"><i class="fas fa-users"></i></div><div class="sv"><?php echo $stats['users']; ?></div><div class="sl">Total Users</div></div>
      <div class="sc p"><div class="si"><i class="fas fa-bus"></i></div><div class="sv"><?php echo $stats['buses']; ?></div><div class="sl">Total Buses</div></div>
      <div class="sc y"><div class="si"><i class="fas fa-ticket-alt"></i></div><div class="sv"><?php echo $stats['bookings']; ?></div><div class="sl">Total Bookings</div></div>
      <div class="sc r"><div class="si"><i class="fas fa-rupee-sign"></i></div><div class="sv">₹<?php echo number_format($stats['revenue'],0); ?></div><div class="sl">Revenue (Paid)</div></div>
      <div class="sc o"><div class="si"><i class="fas fa-clock"></i></div><div class="sv"><?php echo $stats['pending']; ?></div><div class="sl">Pending</div></div>
    </div>

    <div class="sh"><div class="sec-title">Recent <span>Bookings</span></div></div>
    <div class="tw">
      <table class="tbl">
        <thead><tr><th>PNR</th><th>User</th><th>Bus</th><th>Route</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach($recent as $b): ?>
        <tr>
          <td><span style="font-family:'Syne',sans-serif;color:var(--accent2);font-weight:700;font-size:11px;letter-spacing:1px"><?php echo $b['pnr']; ?></span></td>
          <td><?php echo $b['username']; ?></td>
          <td><?php echo $b['bus_name']; ?></td>
          <td><?php echo $b['from_city']; ?> → <?php echo $b['to_city']; ?></td>
          <td><strong style="color:var(--accent)">₹<?php echo number_format($b['total_amount'],2); ?></strong></td>
          <td><span class="badge <?php echo $b['payment_status']; ?>"><?php echo ucfirst($b['payment_status']); ?></span></td>
          <td style="color:var(--muted);font-size:12px"><?php echo date('d M Y',strtotime($b['booking_date'])); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- MANAGE BUSES -->
  <section id="buses" class="sec">
    <div class="sh">
      <div class="sec-title">Manage <span>Buses</span></div>
      <button class="abtn" onclick="toggleAddForm()"><i class="fas fa-plus"></i> Add Bus</button>
    </div>
    <div class="form-panel" id="addBusForm">
      <div class="sec-title" style="margin-bottom:18px;font-size:15px">Add New <span>Bus</span></div>
      <form method="POST">
        <input type="hidden" name="add_bus" value="1">
        <div class="fgrid">
          <div class="fg" style="grid-column:1/-1"><label>Bus Name</label><input type="text" name="bus_name" placeholder="e.g. AC Sleeper - KPN Travels" required></div>
          <div class="fg"><label>From City</label><input type="text" name="from_city" required></div>
          <div class="fg"><label>To City</label>  <input type="text" name="to_city"   required></div>
          <div class="fg"><label>Travel Date</label><input type="date" name="travel_date" required></div>
          <div class="fg"><label>Total Seats</label><input type="number" name="total_seats" required></div>
          <div class="fg" style="grid-column:1/-1"><label>Price Per Seat (₹)</label><input type="number" step="0.01" name="price_per_seat" required></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="sbmt">🚀 Add Bus</button>
          <button type="button" class="cncl" onclick="toggleAddForm()">Cancel</button>
        </div>
      </form>
    </div>
    <div class="tw">
      <table class="tbl">
        <thead><tr><th>ID</th><th>Bus Name</th><th>Route</th><th>Date</th><th>Price</th><th>Seat Availability</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($buses as $bus):
          $pct = $bus['seat_count']>0 ? round(($bus['avail_seats']/$bus['seat_count'])*100) : 0;
          $col = $pct>50 ? 'var(--accent)' : ($pct>20 ? 'var(--yellow)' : 'var(--red)');
        ?>
        <tr>
          <td style="color:var(--muted)"><?php echo $bus['id']; ?></td>
          <td><strong><?php echo $bus['bus_name']; ?></strong></td>
          <td><?php echo $bus['from_city']; ?> → <?php echo $bus['to_city']; ?></td>
          <td><?php echo date('d M Y',strtotime($bus['travel_date'])); ?></td>
          <td><strong style="color:var(--accent)">₹<?php echo $bus['price_per_seat']; ?></strong></td>
          <td>
            <div style="font-size:13px;color:<?php echo $col; ?>;font-weight:700"><?php echo $bus['avail_seats']; ?>/<?php echo $bus['seat_count']; ?></div>
            <div class="abar"><div class="afill" style="width:<?php echo $pct; ?>%;background:<?php echo $col; ?>"></div></div>
          </td>
          <td><button class="dbtn" onclick="delBus(<?php echo $bus['id']; ?>)"><i class="fas fa-trash"></i> Delete</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ALL BOOKINGS -->
  <section id="bookings" class="sec">
    <div class="sh"><div class="sec-title">All <span>Bookings</span></div></div>
    <div class="msr">
      <div class="ms"><div class="ms-val" style="color:var(--accent)"><?php echo $stats['paid']; ?></div><div class="ms-lbl">Paid</div></div>
      <div class="ms"><div class="ms-val" style="color:var(--yellow)"><?php echo $stats['pending']; ?></div><div class="ms-lbl">Pending</div></div>
      <div class="ms"><div class="ms-val" style="color:var(--text)">₹<?php echo number_format($stats['revenue'],0); ?></div><div class="ms-lbl">Revenue</div></div>
    </div>
    <div class="tw">
      <table class="tbl">
        <thead><tr><th>PNR</th><th>User</th><th>Bus</th><th>Seats</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($all_bk as $b): ?>
        <tr>
          <td><span style="font-family:'Syne',sans-serif;color:var(--accent2);font-weight:700;font-size:11px;letter-spacing:1px"><?php echo $b['pnr']; ?></span></td>
          <td><?php echo $b['username']; ?></td>
          <td><?php echo $b['bus_name']; ?></td>
          <td><strong><?php echo $b['seat_numbers']; ?></strong></td>
          <td><strong style="color:var(--accent)">₹<?php echo number_format($b['total_amount'],2); ?></strong></td>
          <td><span class="badge <?php echo $b['payment_status']; ?>"><?php echo ucfirst($b['payment_status']); ?></span></td>
          <td><button class="dbtn" onclick="cancelBk(<?php echo $b['id']; ?>)"><i class="fas fa-ban"></i> Cancel</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- USERS -->
  <section id="users" class="sec">
    <div class="sh"><div class="sec-title">All <span>Users</span></div><span style="font-size:12px;color:var(--muted)"><?php echo $stats['users']; ?> registered</span></div>
    <div class="tw">
      <table class="tbl">
        <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Password</th><th>Phone</th><th>Joined</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
        <tr>
          <td style="color:var(--muted)"><?php echo $u['id']; ?></td>
          <td><strong><?php echo $u['username']; ?></strong></td>
          <td><?php echo $u['email']; ?></td>
          <td><code style="background:var(--bg);padding:3px 8px;border-radius:5px;font-size:12px;color:var(--yellow)"><?php echo $u['password']; ?></code></td>
          <td><?php echo $u['phone'] ?: '—'; ?></td>
          <td style="color:var(--muted);font-size:12px"><?php echo date('d M Y',strtotime($u['created_at'])); ?></td>
          <td><button class="dbtn" onclick="delUser(<?php echo $u['id']; ?>)"><i class="fas fa-trash"></i></button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<div class="toast" id="toast"></div>

<script>
const titles={dashboard:'Dashboard <span>Overview</span>',buses:'Manage <span>Buses</span>',bookings:'All <span>Bookings</span>',users:'All <span>Users</span>'};
function show(id,el){
  document.querySelectorAll('.sec').forEach(s=>s.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('.ni').forEach(n=>n.classList.remove('active'));
  if(el) el.classList.add('active');
  document.getElementById('pgTitle').innerHTML=titles[id];
}
function toast(msg,ok=true){
  const t=document.getElementById('toast');
  t.textContent=(ok?'✅ ':'❌ ')+msg;
  t.style.borderColor=ok?'var(--accent2)':'var(--red)';
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),3000);
}
function toggleAddForm(){
  const f=document.getElementById('addBusForm');
  f.style.display=f.style.display==='block'?'none':'block';
}
function delBus(id){
  if(!confirm('Delete this bus and all its seats?')) return;
  fetch(`?action=delete_bus&id=${id}`).then(r=>r.json()).then(d=>{if(d.success){toast('Bus deleted');setTimeout(()=>location.reload(),1200);}});
}
function cancelBk(id){
  if(!confirm('Cancel this booking and release seats?')) return;
  fetch(`?action=cancel_booking&id=${id}`).then(r=>r.json()).then(d=>{if(d.success){toast('Booking cancelled');setTimeout(()=>location.reload(),1200);}});
}
function delUser(id){
  if(!confirm('Delete this user?')) return;
  fetch(`?action=delete_user&id=${id}`).then(r=>r.json()).then(d=>{if(d.success){toast('User deleted');setTimeout(()=>location.reload(),1200);}});
}
// Auto open section from URL
const sec=new URLSearchParams(location.search).get('sec');
if(sec){const el=[...document.querySelectorAll('.ni')].find(l=>l.textContent.trim().toLowerCase().includes(sec));if(el) show(sec,el);}
</script>
</body>
</html>
