<?php require_once __DIR__ . '/assets/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pitercell — Seu celular novo de novo</title>

  <!--fontes e estilos -->
  <link rel="icon" href="assets/img/logo_piterCell.jpg" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet" />
  
  <!--css-->
  <link rel="stylesheet" href="assets/css/main.css" />

</head>
<body>
<!-- NAVBAR -->
<nav>
  <div class="nav-inner">
    <a href="index.php" class="logo">
      <img src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/7QCEUGhvdG9zaG9wIDMuMAA4QklNBAQAAAAAAGgcAigAYkZCTUQwYTAwMGFiNzAxMDAwMDY2MDMwMDAwM2QwNTAwMDA2NTA1MDAwMDI3MDYwMDAwZTAwODAwMDBlYzBkMDAwMDQ2MGUwMDAwN2QwZTAwMDBkZjBlMDAwMDIxMTQwMDAwAP/bAIQABQYGCwgLCwsLCw0LCwsNDg4NDQ4ODw0ODg4NDxAQEBEREBAQEA8TEhMPEBETFBQTERMWFhYTFhUVFhkWGRYWEgEFBQUKBwoICQkICwgKCAsKCgkJCgoMCQoJCgkMDQsKCwsKCw0MCwsICwsMDAwNDQwMDQoLCg0MDQ0MExQTExOc/8IAEQgAlgCWAwEiAAIRAQMRAf/EAIIAAAEFAQEAAAAAAAAAAAAAAAABAgMFBwQGEAABAwICBQoDAwoHAAAAAAABAAIRAyESMQQQIkFREyAyYXGBkaHB0TCx8CMzQBRCUFJicoKywvEFNGBzg5LSEQABAwIEBQUBAQEAAAAAAAABABEhMUFRYXGBEJGhsfAgwdHh8TBAUP/aAAwDAQACAAMAAAABzborFUtuircpeS0Eoe14aFqFnHRgWnDzxB0REALNzgdXNLyISank2shksnPOD4+nkU6ZWyqEnO4GRqIO7KtwSRv97IZ5NA6MfzOjQTYMc2MMi7eJoSvjulPfOi752+V8r7ShceeGR8zppPWetcZxZ3lf2u8QnraFrqxqJwxrsOObGGPK1Q9Tc0NxO22mq3otjWXlIEXhtTzBT31z5bueWdPPwSPi8Z7fw73xgldEmy41soY6itC/7/I2UielIIpUu3c9ai+xzHmjjX2ElLYdCdcMLp3TU9r52WSvHJTxN2XGtlDHWuaA508qWnGxHJ6fx/eInPBZtacVnyRuf1dNVG9bGnmjYDBIUTZsa2UMbRZA92lbWyp6Hr8v2KWnX5/sUsFo+2RVqbKncXHLa0k0jqy+4grK2+oeRibNjOzc6Y2iqEt354C26KFoXy0IFv2+bUL1lKgei46gD0nJTAKgAbNjOzBjSoAORAc9iAgAAgD2vYCKIAAAABs2M7MGMrsoGNJswGMmzAYybMBjJswGNt2YDGTZgMZNmAxk2YDGdmAP/9oACAEBAAEFAsaDr41Ka5cpgIqtWMckai5UlOqJz9R1SjZBYk1ShhwuIacSc9ByB+zmwU6pWLU1PQOuVCB1FApp2JUoKowNR1TqPMlFErDs8m9CmS6QhZEaghdN0FtIOMoIoa6WQVNheW0qWitqUk8wqtPCpUoK7lobgyppWm8oi0jUUERGqE1aI5rHflFB6dpVEqpUpE1nNI3Ff4fobXqpUtWojFEESq4aCdRM6pWh5The90snaeYFaYwWwy5rrmtiEODmjEDU2edo3RBvRqFWCpDYqXWDZYdthg03pr9pvRqbY52jmwuZlOshUkTiZTrNLCsUrGmlRbJaQOfQq4FjDhWqgLe/SmhpGrRqiOTVNiJVd+I8+j0NJ6WK+83ELcK4KOkJukNKq15+BhQbCwLAuRC5ELkguSCwBYVhWHn1KIpM5Br6WkUm0Aymw0tFps0lxpLRabKgphji3Rmh35RTTKMsqDCNIpcnT0ZmN+kjC/W3OrprgjpT6i0ytyifX+w0HSBSBrKnWaylR0prmaPpDRTdSpgflYNOs/ENIqCsqJbTc9rXHmUwC51KmA+mwO5OnHIsltFmJ7GBnJUk6nSVSnTDzSpqmymWOo0pcxmD8WfwU/oL/9oACAEDAAE/AfwA+EEWWn4IVT834ITjbmSpWMBYxrPwiBI4WTv0B//aAAgBAgABPwH8BTp4zGXWjzAmmERM5dWpreNkynbCHAAnOL3CraJhZjDrTEGx7uI9OY3IIhNVTJiiVT3d/p5LTp+zviEG/XN/TmNdkEcllmnOmOpNuPrcmAeTj3xPstLdhYW7p2eLnfnHsF+/mNok3EeIRaYiPMJ14ssBTZahVeOHZAVVznmXcwmP7LecrBNE8PJNA2biSJ3ITALQ3eTJb6o2bbDlxZn33T6P2jDLMIwjpDdmtIZhcTbaJiCD8v0B/9oACAEBAAY/Alms9fEFNQ7R/Urc+3OO1tcFYyp1Bd49UfjjX3+6PxOxTqa4cYd1H+y6LvBBuXaiBlNifXmwLkq+07edw7EV3c06g0ZlC93fncT6DVH627ipGXyQ7NdgVLrQD4rAzvPFXBHwCSY3X80MbmGLiV943xU42SMkdoKd2rG+/BvqfRMwts63DyQBAOfksLRAGcLq3K2e/nE9aef2Wk+acey3DC66/wCdv8qH+7V9RPiQndQMniYyTeED5KOuFs2cPMcD9WTDl9oJndmPmmYjJ2/kEP1Rn1nP+/FF3hz/AOL0Cd+6P6kBuwj6+ad1QI/ZMW9uCyDoe6MX72cqqf2Xlv8AETfvTexvyQ/e9UT9dfoozDpPbKA/VxR2QFHFw/lThz4607sE+a4Et9UPreoG97vUjuTyTJwkIGbBo8hqBGUZIHt+Sk7lwg+gXCVO8/V+eZ3qRx9lCb2n5FFrRc2Q1YfBf9vMld7l2n2X1vXUPgfxey7kPrdqGrrW0rDj5raEQob4/A6QVnjxPsum3z9lHKNjtPsvvGefsvvGeJ9l943xPsum3z9l0m+fsukFmFnz2nBimMh1Jz8GGJ3QbKmXNHlw90a2AQJ3DcrMADB1ZnsX+WPg1VHloF7CBuCg0cPWQFU3gGB1WkroeQWPBM3yRPJR2gJts4+SA7URw5gWyUA47MiULzErkwdw9ynXglffu8QrHav80cRwv45JzCbnFJ7V0/kmtDsEAX32QBql1+pCXC3WESHbupFxfn2c0TlN0++REbW63imjFYm5mYGL/wAp+4giBjBt6p8uyNtodGDHnCu+G4bGRmR7oEHbtN+1N2u0SBfDOfW5HayMZznF+7a8kwA7Jz2gYvx7Lp3HCIGMZ3379yJLodtQOMAR5oDFGd8QOUX6pE2Ug37RxNozy3/6Q//aAAgBAQEBPyGZfN0ayMiiTCzDQ/qIEXIyT4DGiBkfwjr5inUEAEAH3uT77LihQ+aMIxRKJyrhPyPZPUuJqsO4o2jP38lSfftxNQ83UgSDg0qX6KjK0y83ZQIA1rmjEcu6n2I5ar1g7FFE6o2CipVSk0sMUXIYqDo3bQdl1FCBZaFKglZCdDjzFUcmXQEo1BS6KpUjG6uQxwRd46BfkixQMp66p0HMW2BGDCvdOF0XPBot0NkaMwUTD3vwmvEyagQALk5AOVXR5UDtDHoieX3TUiidkF8EBMAcsBiSgN4Yc0sCykYmuSeFiSeaq4NXArw6IoUrJWHmAqUDC0oDnfaodLphCMGZcAXME/uer4wRQgGQPJomrIDAEgIEOHQPkYdymMhBsF8gFWXXgBCHtwVJ4QgeB5LAEnAB0NwQDKK/Rt0BJBuGPDcCASoeRogWCxE3EhEEgwsFWjmiQCEB+LhgvWimiErAxbgIQTLOwhGBKRWMAvVByDVISzyaYQ7yniS8+YY6a1tC5JgoPvLgBThOgkbwtfIAFuZQHQUqafhEeCzi8CYXuH3loQFxisdCIGyDYmozteZALdPjd21Jk3AdThTQsEMlZiD87oMMYcxZCqgOBYPQZs6tRE4DNAcBtgdFsbzGsNAXfSjlNqgN9LaX2KOjR2YPVtXDlgifUbHqRy9QAtgCamIGeMg2d1Z0NxIMo8gSTyIhMMBs4pJ3HkBAAqB6yBZoFcMkXF8mJg+VBYMAHznmaWwQ5OQDAJEHMdkSe4IHSXHZ7s7aInCx2MQyOiIATJBG/wChGIMEVToeibcHtkQB7IsYAyFSZt88sUDLKBsVKWnRRSkuDWjCBMlrOpauXoXD7FzE40KJO7WcAAmwDmcAKMmb5AvRh900hxqJQIGxJxbJerWgvF0QikB/I1O8wc5xJAB2ADC5qhA5LIsKvR1tuoeSzjmYGT+SShsDgzAZC+akWFZohwHEBBQyRYj9K0MNYuD7CjYJ3rTH8RPhVUaRJQwmCapkZuFQ9fePdTiQ7sehY+6NwC0uAHEOz7qs7OFs2PwTiXiwh5O+TLk4Pc8BwHoLl7o9iifwxRl2cnZ6JctShgeUT8EIdQC4TUxiMA45W0omLkyPsHymR2w0jlGAQQLk1KMac+FNe3pAe4CAn5T8K3eiDTJe1QCbZIUH3HDxlSz9ir9pDftPwiH3fSIYPNlT0HgVQEAMEnETCJHBqsIEEM14R2B2cAOLX7hVMMaQulzYYojkAQRIgAsgB4RgSAUA1afNDwQY+RMBZySoELGi9rolACXJEHIdp4FECVSAGpYAPAACdw2XYuaIUCCw7TcVGDwWNB8oIEAQERLcShBfgOH0dHQ0Xd73ihUYpYCHunVI5V6/iCABuAG5PcQAkRWIAPuUUggtkThJhAgQBq5hGjIQAkAFmPQhDaAsibXBNUSEEWFHkhafUQIJE5qnE4JIDElslDUHs1xsdEFATFybjbYKfgTmUOVPQAXvCDAtvHNGgPITAqBHMg5EYIf7JBIAFxFh8ZF6oiogkuAOSiAwAY0CvJZlkRM0wPMCIJcogYs9DJIYQN09YoJVlDZChObQrZ7u5ECyDAQA2kHFBROFjLigSAYSKkYlKQBaUXaAJwoTGcCOrFMQAoKMWRUQXOBg1B3bgEVTENGDCzgJgKKCkEBCvz7PGQUaABsLo+oB/wDGYhAOtHpP9Qz1PP8AJ0/8HT/w/9oADAMBAQIBAwEAABAhxAh7aLjIgrwyDoJVrIgIYnkCoVxqjB1oO/8Ay8MgwPV2wv5zwAQwHG4QnoQg0AQTKB0JVoYokoEw888IsAEwsMs8QAgQwAAQAAAA/9oACAEDAQE/EP8AAD/ywfqbl2MHbH+TeDdf5aDlmbn1HgBQN+HhRS/jDbZVXuKnNVHMlv8Agf/aAAgBAgEBPxD/AAGGO5QKHolwIuEOVMTECPlTRVb4hs5+uIeTbjG+PJ/CEAkTgwAXiAZPYPQD7D6O/D4E/M4GS0NF74Mp1XmnqADBHiC+nAhAeW2hIKwvYRHoWx7uj/gf/9oACAEBAQE/EBdPc8upYP6hJfqroQe8SDugFNw5iRFOVEdsbk1BwLx8AlsK4C0AMFDJCA7Htk4guWQUZUxwQ2wAH2IW7UHBymcYGc38KiDm3Y0CFM2N8vK/FaCi5F2EECEuInQ/jalSZm7kO0CirwNlWQXUYBAK4uHLFkhh9lYZiMjTJC/qMjrqQGH7C52gUJ3GuoOpLLpFklHvB5gE+A2n0oPMK5R8oxdE4EWDmU7UKYZe2U3QgQ3VYuoojsWnTXDHWoGSnWF2/Ym/JRQwr4fAqkjSQs4QMMeihTxN5541L6fpVnx1IMNTJ2aAokMjAPFUDT7UqDIDB7C1gx9vChkcpjsLviDjDTjm5oQFAkqXTVYi5PeM5JggYfFChCzmHnT4WOT8b6tUQgZReqjNuK5R/gUapbAc0UtSIQCUapAAts3ErwbIqLy1RRwiFRJm/ZAuZHKZk4AcmCEeMoflA9x0Yi6GduCAYFJpmhNErM+xq+BV5q0BPayNQehJQJDDXNCT8z4UdBONOfcKCfQx8AfaDdCXCeI4RqcIHDVXsZWT+osoUDqq4LkEMcl1B/sQANRRq0KveME4ESHLisq/MsEpByiBebNLFJyUzEfWpUObmlD9rVWy5sG5orzgXNDzMVYogNFIDcK4oujZVIxOHeRYSXmmxcBg+ZIzhRBuEApQx3cqJOSoTzgPzoTrt6EJKSoJYM/R3EKT5yz80qQLErGaAI/KIxJgCNoGo5k1UNiQFLHxiGHkEe+YO4kODILtFAF6AgE28iFg88u1HvVIGAKjUSyKKs45olCux7cCgOGK3Lrl8k6xqtYGSODS9PRSQ8hvCAuqqtPGUH7iVBYClkcScyo7AooeU96ZuhfcwziZVAYtAGtyfakWnV2RqX05SMrUIDAcMBxCNGTr2ntwBToJGvN0k5y2VXN3BIfh1Qjxsn5Cwuon1J8PingHVJWMNAc9IZLEUrflDQJBxcDUJHBeQKAG4UQkEDubirjgdmQRlp0BoweVGyUt4D9hKNvPMStxZBfoqjAkB5qK9waX0GY4ZouGuZo7AQhlXse3E3AehzwXLRhUTTSAiIYfPHFXzCEGJGwQEJm4LV3BFEM7xThvA63v0aHFFNcAsDvQUflATcp8kCk0EpaOZ2cQZWHFBLA5WBmitU7sXczJ6h6Kqu0KPEyAVFiv4q9Bcvdy7J4A0E5p+ScYlzdSi1Jgh/UEBaYiSKMhgavHoQeGMSOtc/MSbxUtm+AkQmwYaJBhYlnqKHAEo8Rdy/FZFEsnerBCa4lcv+nrZvDDdiJUqgVr+yEBv7lCRrRPoFWtKA8ZC6C4W0/yAFDln4hXRMgp8DjKjlYxm4TbY4A3XRXWGWDg4sKwYyBdVPsNL2hyYNdC0TmYsR8L7IKAf0YL+uCU0shaMHUjjZ0iNbWqgwINBDJfgmzLaABATTVDiABgr40DDvsqosShYgsi4fAkWKsAoeFAXIQ+r4obEmxJyUqQcQgoEQMQqnIIMwbs0U2DcwGyoU4bqxdyMIulIQBSVhzT16Bi2mZPKieSBGZEOx+ghPQgAaaMTVTeG0f3THMsD1bgUUbcAhGAZli0ehBhXyOlmcM13J5iZObfQnjGqaCjRniGZYgG4LAzxgoClG5g0wa8Il8Jh019i2aG7OML7rXKMXWe6kHP6AOQYniBizte3G2BmqnbbkOCUYCVs04ASjDj2gnMUNRV8BFZtXlaSyFd+wZbi/FwBiimQ4BAOiOA9Y41qs+GIMw0G+/BuISj/I8CUIlP6Cz+J+I6dP6HRn6D8H4//9k=" alt="Pitercell" />
    </a>
    <ul class="nav-links">
      <li><a href="#" class="nav-link active">Início</a></li>
      <li><a href="servicos.php" class="nav-link">Serviços</a></li>
      <li><a href="#" class="nav-link">Produtos</a></li>
      <li><a href="#" class="nav-link">Ofertas</a></li>
      <li><a href="#" class="nav-link">Sobre</a></li>
    </ul>
    <div class="nav-actions">
      <div class="icon-btn">
        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <div class="icon-btn">
        <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span class="badge">3</span>
      </div>
      <button class="theme-toggle" data-theme-toggle type="button" aria-label="Ativar tema escuro">🌙</button>
      <div class="divider-v"></div>
        <button class="cta-nav" onclick="window.location.href='assets/pages/login.php'">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Entrar
        </button>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-glow"></div>
  <div>
    <div class="hero-tag"><span></span>Assistência técnica especializada</div>
    <h1>Seu celular<br><em>novo de novo</em></h1>
    <p>Conserto, manutenção e venda de celulares com qualidade garantida. Android e iOS em mãos especializadas.</p>
    <div class="hero-btns">
      <a href="<?= h(whatsappUrl()) ?>" target="_blank" rel="noopener" class="btn-primary">
        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        Solicitar orçamento
      </a>
      <a href="servicos.php" class="btn-ghost">
        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
        Ver serviços
      </a>
    </div>
    <div class="hero-stats">
      <div class="stat-item"><span class="stat-num">2k+</span><span class="stat-label">Clientes atendidos</span></div>
      <div class="stat-item"><span class="stat-num">98%</span><span class="stat-label">Satisfação</span></div>
      <div class="stat-item"><span class="stat-num">1h</span><span class="stat-label">Entrega expressa</span></div>
    </div>
  </div>
  <div class="hero-visual">
    <div class="float-badge">
      <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span>Garantia de 90 dias</span>
    </div>
    <div class="phone-card">
      <div class="phone-top">
        <div class="phone-icon">
          <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
        </div>
        <div class="phone-info"><h4>iPhone 13 Pro</h4><p>Diagnóstico em andamento</p></div>
      </div>
      <div class="phone-badge">✓ Peça original</div>
      <div class="phone-progress">
        <p>Progresso do reparo</p>
        <div class="progress-bar"><div class="progress-fill"></div></div>
      </div>
      <div class="phone-services">
        <div class="service-row">
          <div class="service-row-left">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            <span>Troca de tela</span>
          </div>
          <span class="service-row-right">R$ 299</span>
        </div>
        <div class="service-row">
          <div class="service-row-left">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.5 8A6.5 6.5 0 0 0 12 2v2a4.5 4.5 0 0 1 0 9v2a6.5 6.5 0 0 0 6.5-7z"/></svg>
            <span>Bateria</span>
          </div>
          <span class="service-row-right">R$ 149</span>
        </div>
      </div>
    </div>
    <div class="float-badge2">
      <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <span>Diagnóstico grátis</span>
    </div>
  </div>
</section>

<!-- SERVIÇOS -->
<section class="section">
  <div class="section-tag">O que fazemos</div>
  <h2>Nossos <em>Serviços</em></h2>
  <p class="section-sub">Especialistas em Android e iOS com peças originais e garantia em todos os reparos.</p>
  <div class="services-grid">
    <div class="service-card">
      <div class="service-icon"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
      <h3>Troca de Tela</h3>
      <p>Tela quebrada ou sem toque? Fazemos a substituição com display original em até 1h.</p>
      <div class="service-price"><span class="price-from">a partir de</span><span class="price-val">R$ 200,00</span></div>
    </div>
    <div class="service-card">
      <div class="service-icon"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18.5 8A6.5 6.5 0 0 0 12 2v2a4.5 4.5 0 0 1 0 9v2a6.5 6.5 0 0 0 6.5-7z"/><path d="M5.5 8A6.5 6.5 0 0 1 12 2v2a4.5 4.5 0 0 0 0 9v2A6.5 6.5 0 0 1 5.5 8z"/><circle cx="12" cy="13" r="2"/></svg></div>
      <h3>Troca de Bateria</h3>
      <p>Bateria viciada ou com autonomia ruim? Recolocamos com bateria original certificada.</p>
      <div class="service-price"><span class="price-from">a partir de</span><span class="price-val">R$ 100,00</span></div>
    </div>
    <div class="service-card">
      <div class="service-icon"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
      <h3>Diagnóstico Grátis</h3>
      <p>Não sabe o que tem? Fazemos avaliação completa do aparelho sem custo algum.</p>
      <div class="service-price"><span class="price-from">sempre</span><span class="price-val">Grátis</span></div>
    </div>
    <div class="service-card">
      <div class="service-icon"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></div>
      <h3>Conector de Carga</h3>
      <p>Carregando com dificuldade? Substituímos o conector original para carga rápida.</p>
      <div class="service-price"><span class="price-from">a partir de</span><span class="price-val">R$ 80,00</span></div>
    </div>
    <div class="service-card">
      <div class="service-icon"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
      <h3>Câmera e Flash</h3>
      <p>Câmera embaçada, sem foco ou flash defeituoso? Resolvemos com peça original.</p>
      <div class="service-price"><span class="price-from">a partir de</span><span class="price-val">R$ 50,00</span></div>
    </div>
    <div class="service-card">
      <div class="service-icon"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div>
      <h3>Software e Formatação</h3>
      <p>Celular travado, lento ou com vírus? Formatamos e otimizamos o sistema completo.</p>
      <div class="service-price"><span class="price-from">a partir de</span><span class="price-val">R9</span></div>
    </div>
  </div>
</section>

<!-- BANNER CTA -->
<div class="cta-banner">
  <div>
    <h2>Pronto para deixar seu<br>celular <em>novo de novo?</em></h2>
    <p>Fale com a gente agora e receba um orçamento sem compromisso. Atendimento rápido e peças originais.</p>
  </div>
  <a href="<?= h(whatsappUrl()) ?>" target="_blank" rel="noopener" class="btn-primary" style="font-size:15px;padding:14px 28px">
    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
    Falar no WhatsApp
  </a>
</div>

<!-- FOOTER -->
<footer>
  <img src="assets/img/logo_piterCell.jpg" alt="Pitercell">
  <p>© 2026 Pitercell — Seu celular novo de novo</p>
</footer>
<script src="assets/js/theme.js"></script>
</body>
</html>
