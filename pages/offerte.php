<?php
session_start();
?>
<!DOCTYPE html>
<html lang="nl">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Offerte Aanvragen | Contact | Actief Brandbeveiliging B.V.</title>
  <link rel="stylesheet" href="../styles.css?v=1759338833" />
  <link rel="icon" href="../favicon.ico" type="image/x-icon">
  <script src="../script.js?v=1759338833"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <script src="https://kit.fontawesome.com/ae38b79a86.js" crossorigin="anonymous"></script>
</head>

<body>
  <header>
    <nav class="navbar">
      <a class="logo animate__animated animate__fadeInDown" href="../index.html">
        <img src="../img/logo.png" alt="Actief Brandbeveiliging Logo" />
      </a>
      <div class="mobile-menu">
        <div class="hamburger"></div>
        <div class="hamburger"></div>
        <div class="hamburger"></div>
      </div>
      <script>
        document.addEventListener("DOMContentLoaded", function () {
          const mobileMenu = document.querySelector(".mobile-menu");
          const navList = document.querySelector(".nav-list");

          mobileMenu.addEventListener("click", function () {
            navList.classList.toggle("active");
          });
        });
      </script>
      <ul class="nav-list">
        <li class="fade-in">
          <a href="../pages/onsbedrijf.html">ONS BEDRIJF</a>
        </li>
        <li class="fade-in">
          <a href="../pages/onzediensten.html">ONZE DIENSTEN</a>
        </li>
        <li class="fade-in">
          <a href="../pages/onzeproducten.html">ONZE PRODUCTEN</a>
        </li>
        <li class="fade-in"><a href="../pages/nieuws.html">NIEUWS</a></li>
        <li class="fade-in"><a href="../pages/offerte.php">OFFERTE</a></li>
      </ul>
    </nav>
  </header>
  <main>

    <section id="services">
      <div class="container" data-wow-offset="10" data-wow-delay="0.5s">
        <h1 class="section-title">Offerte Aanvragen</h1>
        <p class="section-description">Vraag vrijblijvend een offerte aan voor uw brandbeveiliging</p>

        <div class="contact-layout">
          <div class="contact-form-section">
            <div id="main-contact-form" class="contact-form">
              <div id="contactUpdatePanel">
                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                  <div class="message-box success-message">
                    <div class="message-icon">
                      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                      </svg>
                    </div>
                    <div class="message-content">
                      <h3>Bedankt voor uw aanvraag!</h3>
                      <p>We hebben uw offerte aanvraag succesvol ontvangen. Een van onze specialisten neemt binnen 24 uur contact met u op.</p>
                      <p class="message-note">Controleer ook uw spam folder voor onze bevestigingsmail.</p>
                    </div>
                  </div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
                  <div class="message-box error-message">
                    <div class="message-icon">
                      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                      </svg>
                    </div>
                    <div class="message-content">
                      <h3>Er is een probleem opgetreden</h3>
                      <p>Het formulier kon helaas niet worden verzonden. Probeer het later opnieuw.</p>
                      <p class="message-note">U kunt ook direct contact met ons opnemen via telefoon: <strong><a href="tel:+31402630298">040 - 263 02 98</a></strong></p>
                    </div>
                  </div>
                <?php endif; ?>
                <form class="pure-form pure-form-stacked offerte-form" method="POST" action="../process-offerte.php">
                  <!-- CSRF Token -->
                  <input type="hidden" name="form_token" value="<?php echo session_id(); ?>">
                  <!-- Honeypot for spam protection -->
                  <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

                  <div class="form-section">
                    <h3 class="form-section-title">Bedrijfsgegevens</h3>

                    <div class="form-group">
                      <label for="company">Bedrijfsnaam <span class="required">*</span></label>
                      <input name="company" type="text" id="company" class="form-input" required placeholder="Bijv. Acme B.V.">
                    </div>
                  </div>

                  <div class="form-section">
                    <h3 class="form-section-title">Contactpersoon</h3>

                    <div class="form-row">
                      <div class="form-group">
                        <label for="firstname">Voornaam <span class="required">*</span></label>
                        <input name="firstname" type="text" id="firstname" class="form-input" required placeholder="Voornaam">
                      </div>

                      <div class="form-group">
                        <label for="lastname">Achternaam <span class="required">*</span></label>
                        <input name="lastname" type="text" id="lastname" class="form-input" required placeholder="Achternaam">
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group">
                        <label for="emailaddress">E-mailadres <span class="required">*</span></label>
                        <input name="emailaddress" type="email" id="emailaddress" class="form-input" required placeholder="naam@bedrijf.nl">
                      </div>

                      <div class="form-group">
                        <label for="phonenumber">Telefoonnummer <span class="required">*</span></label>
                        <input name="phonenumber" type="tel" id="phonenumber" class="form-input" required placeholder="06 12345678">
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="address">Adres, Postcode, Plaats</label>
                      <input name="address" type="text" id="address" class="form-input" placeholder="Straat 123, 1234 AB Plaats">
                    </div>
                  </div>

                  <div class="form-section">
                    <h3 class="form-section-title">Interesse</h3>
                    <p class="form-section-description">Selecteer de producten of diensten waar u een offerte voor wilt ontvangen:</p>

                    <div class="checkbox-grid">
                      <label class="checkbox-item">
                        <input id="checkbox1" type="checkbox" name="checkbox[]" value="Onderhoud Brandmeldsystemen">
                        <span>Onderhoud Brandmeldsystemen</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox2" type="checkbox" name="checkbox[]" value="Brandblussers">
                        <span>Brandblussers</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox3" type="checkbox" name="checkbox[]" value="Onderhoud Kleineblusmiddelen">
                        <span>Onderhoud Kleineblusmiddelen</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox4" type="checkbox" name="checkbox[]" value="Brandslanghaspels">
                        <span>Brandslanghaspels</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox5" type="checkbox" name="checkbox[]" value="Onderhoud Noodverlichting">
                        <span>Onderhoud Noodverlichting</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox6" type="checkbox" name="checkbox[]" value="Brandmelders">
                        <span>Brandmelders</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox7" type="checkbox" name="checkbox[]" value="BHV Trainingen">
                        <span>BHV Trainingen</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox8" type="checkbox" name="checkbox[]" value="Blusdekens">
                        <span>Blusdekens</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox9" type="checkbox" name="checkbox[]" value="EHBO-Koffers">
                        <span>EHBO-Koffers</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox10" type="checkbox" name="checkbox[]" value="Noodverlichtingen">
                        <span>Noodverlichtingen</span>
                      </label>

                      <label class="checkbox-item">
                        <input id="checkbox11" type="checkbox" name="checkbox[]" value="Pictogrammen">
                        <span>Pictogrammen</span>
                      </label>
                    </div>
                  </div>

                  <div class="form-section">
                    <h3 class="form-section-title">Uw Bericht</h3>

                    <div class="form-group">
                      <label for="message">Vertel ons meer over uw situatie <span class="required">*</span></label>
                      <textarea name="message" id="message" class="form-textarea" rows="6" required placeholder="Beschrijf uw situatie, aantal medewerkers, locaties, specifieke wensen..."></textarea>
                      <p class="form-help-text">Hoe meer informatie u geeft, hoe nauwkeuriger we uw offerte kunnen samenstellen.</p>
                    </div>
                  </div>

                  <div class="form-submit">
                    <button type="submit" name="sendButton" id="sendButton" class="btn-submit">
                      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                      </svg>
                      Offerte Aanvragen
                    </button>
                    <p class="form-privacy-note">Door dit formulier te versturen gaat u akkoord met onze <a href="privacyverklaring.html">privacyverklaring</a>.</p>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="contact-info-section">
            <div class="contact-card">
              <h3><i class="fas fa-map-marker-alt"></i> Adres & Contact</h3>
              <div class="contact-details">
                <div class="contact-item">
                  <h4><i class="fas fa-building"></i> Bezoekadres</h4>
                  <p>Tenierslaan 11-13<br>5613 DZ Eindhoven<br>Nederland</p>
                </div>

                <div class="contact-item">
                  <h4><i class="fas fa-phone"></i> Telefoon</h4>
                  <p><a href="tel:+31402630298">040 - 263 02 98</a></p>
                </div>

                <div class="contact-item">
                  <h4><i class="fas fa-envelope"></i> E-mail</h4>
                  <p><a href="mailto:info@actiefbrandbeveiliging.nl">info@actiefbrandbeveiliging.nl</a></p>
                </div>

                <div class="contact-item">
                  <h4><i class="fas fa-clock"></i> Openingstijden</h4>
                  <p><strong>Maandag - Vrijdag</strong><br>08:30 - 17:00</p>
                </div>
              </div>
            </div>

            <div class="map-container">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2485.145!2d5.4713329!3d51.4474986!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c6d90b6c2e0b45%3A0x4a91a1e8f7c8b9c!2sTenierslaan%2011%2C%205613%20DZ%20Eindhoven%2C%20Netherlands!5e0!3m2!1sen!2snl!4v1640000000000"
                width="100%"
                height="300"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Locatie Actief Brandbeveiliging B.V. - Tenierslaan 11-13, Eindhoven">
              </iframe>

              <!-- Alternative: Direct link to location -->
              <div class="map-fallback">
                <a href="https://www.google.com/maps/place/Tenierslaan+11-13,+5613+DZ+Eindhoven,+Netherlands"
                   target="_blank"
                   rel="noopener"
                   class="map-link">
                  <i class="fas fa-external-link-alt"></i>
                  Open in Google Maps
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>
  <footer class="footer">
    <div class="container">
      <div class="copyright-section wow animate__animated animate__zoomIn" data-wow-offset="10" data-wow-delay="0.5s">
        <div class="row">
          <div class="col-md-4 col-sm-12 col-xs-12 copyright-left">
            <p class="copyright-text">
              © <span class="current-year">2025</span> Actief Brandbeveiliging - All Rights Reserved
            </p>
          </div>
          <div class="col-md-4 col-sm-12 col-xs-12 copyright-center">
            <div class="footer-social-icons">
              <a href="https://www.facebook.com/102245964844282" target="_blank" rel="noopener" aria-label="Facebook">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </a>
              <a href="https://www.linkedin.com/company/actief-brandbeveiliging" target="_blank" rel="noopener" aria-label="LinkedIn">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                  <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
              </a>
            </div>
          </div>
          <div class="col-md-4 col-sm-12 col-xs-12 copyright-right">
            <ul class="footer-nav">
              <li><a href="disclaimer.html">Disclaimer</a></li>
              <li><a href="privacyverklaring.html">Privacy</a></li>
              <li><a href="leveringsvoorwarden.html">Voorwaarden</a></li>
            </ul>
          </div>
        </div>
      </div>
      <script>
        // Auto-update copyright year
        document.querySelectorAll('.current-year').forEach(el => el.textContent = new Date().getFullYear());
      </script>
    </div>
  </footer>

  <script>
    // Auto-check product checkbox based on URL parameter
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const product = urlParams.get('product');

      if (product) {
        // Find and check the checkbox that matches the product
        const checkboxes = document.querySelectorAll('input[type="checkbox"][name="checkbox[]"]');
        checkboxes.forEach(function(checkbox) {
          if (checkbox.value === product) {
            checkbox.checked = true;
            // Scroll to the form section
            const formSection = document.getElementById('main-contact-form');
            if (formSection) {
              formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
          }
        });
      }
    });
  </script>
</body>

</html>