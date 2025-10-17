<!DOCTYPE html>
<html lang="es">
<head>
    
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nuevo Contacto</title>
  <style>
    /* ===== Paleta alineada al sitio (no ne®Æn) ===== */
    :root{
      --bg-top:#0f8a8f;      /* teal claro */
      --bg-bottom:#0b7177;   /* teal m®¢s oscuro */
      --card:#0f1f23;        /* gris azulado muy oscuro */
      --border:#1f3a40;
      --text:#ffffff;
      --muted:#a9bcc0;
      --primary:#1ea6ab;     /* botones/acentos */
      --primary-hover:#178e93;
    }
    *{margin:0;padding:0;box-sizing:border-box}
    body{
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      line-height:1.55;
      color:var(--text);
      background:linear-gradient(180deg,var(--bg-top),var(--bg-bottom));
      padding:20px;
    }

    /* ===== Contenedor ===== */
    .container{
      max-width:640px;
      margin:0 auto;
      background:var(--card);
      border:1px solid var(--border);
      border-radius:14px;
      padding:20px;
      box-shadow:0 10px 24px rgba(0,0,0,.28); /* sombra discreta */
      position:relative;
    }
    /* tira superior muy sutil, sin brillo */
    .container::before{
      content:"";
      position:absolute;left:0;top:0;right:0;height:3px;
      background:linear-gradient(90deg,#1ea6ab,#0b7177);
      border-top-left-radius:14px;border-top-right-radius:14px;
    }

    /* ===== Cabecera ===== */
    .logo{
      display:block;max-width:200px;height:auto;margin:0 auto 16px;
    }
    h1{
      font-weight:800;font-size:1.9rem;letter-spacing:.2px;
      text-align:center;margin-bottom:10px;color:var(--text);
    }
    .lead{
      color:var(--muted);text-align:center;margin:0 auto 14px;max-width:54ch;
    }

    /* ===== Tabla ===== */
    table{
      width:100%;border-collapse:collapse;margin-top:14px;
      background:rgba(255,255,255,.02);
      border:1px solid var(--border);border-radius:10px;overflow:hidden;
    }
    th,td{ text-align:left;padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.05) }
    th{
      font-weight:700;color:#e6ffff;background:rgba(255,255,255,.04);
    }
    tr:hover td{ background:rgba(255,255,255,.03) }

    /* ===== Botones (opcional, por si los usas en este template) ===== */
    .btn{
      display:inline-flex;align-items:center;gap:.5rem;
      background:var(--primary);color:#fff;border:0;border-radius:10px;
      padding:10px 14px;font-weight:600;text-decoration:none;
      transition:filter .2s ease, transform .02s ease;
    }
    .btn:hover{ filter:brightness(.95) }
    .btn:active{ transform:translateY(1px) }

    /* Utilidades */
    .muted{color:var(--muted)}
  </style>
</head>
<body>
  <div class="container">
    <img class="logo" src="https://witmakers.solucionesgt360.com/assets/img/Logos/WITMAKERS%20LOGO%20COLOR.png" alt="WitMakers"/>

    @if($isThankYou ?? false)
      <h1>Gracias por contactarnos</h1>
      <p class="lead muted">Gracias por escribirnos, te estaremos contactando pronto.</p>
    @else
      <h1>Notificaci√≥n de contacto</h1>
      <p class="lead muted">Has recibido una notificaci®Æn de contacto. Estos son los detalles:</p>

      <table>
        <thead>
          <tr>
            <th>Campo</th>
            <th>Valor</th>
          </tr>
        </thead>
        <tbody>
          @foreach($fields as $key => $value)
            <tr>
              <td>{{ $key }}</td>
              <td>{{ $value }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
</body>
</html>
