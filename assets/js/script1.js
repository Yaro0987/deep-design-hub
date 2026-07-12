
        (function() {
            // ========== IMAGE URL RESOLVER ==========
            function resolveImageUrl(src) {
                if (!src) return '';
                if (src.indexOf('http') === 0 || src.indexOf('data:') === 0) return src;
                var apiBase = window.API_BASE || '';
                if (apiBase && src.indexOf('uploads/') === 0) return apiBase + '/' + src;
                return src;
            }

            // ========== SVG SOCIAL ICONS ==========
            var socialIcons = {
                behance: '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M22 7h-7V5h7v2zm1.726 10c-.442 1.297-2.029 3-5.101 3-3.074 0-5.564-1.729-5.564-5.675 0-3.91 2.325-5.92 5.466-5.92 3.082 0 4.964 1.782 5.375 4.426.078.506.109 1.188.095 2.14H15.97c.13 3.211 3.483 3.312 4.588 2.029h3.168zm-7.686-4h4.965c-.105-1.547-1.136-2.219-2.477-2.219-1.466 0-2.277.768-2.488 2.219zm-9.574 6.988H0V5.021h6.953c5.476.081 5.58 5.444 2.72 6.906 3.461 1.26 3.577 8.061-3.207 8.061zM3 11h3.584c2.508 0 2.906-3-.312-3H3v3zm3.391 3H3v3.016h3.341c3.055 0 2.868-3.016.05-3.016z"/></svg>',
                dribbble: '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 0C5.375 0 0 5.375 0 12s5.375 12 12 12 12-5.375 12-12S18.625 0 12 0zm7.938 5.563a10.18 10.18 0 0 1 2.312 6.375c-.338-.063-3.713-.75-7.125-.313-.063-.15-.125-.25-.188-.375-.2-.413-.425-.825-.663-1.225 3.8-1.55 5.5-3.788 5.663-3.988v-.475zM12 1.813c2.638 0 5.038 1.038 6.8 2.725-.138.175-1.675 2.263-5.313 3.613-1.65-3.025-3.475-5.525-3.738-5.875A10.228 10.228 0 0 1 12 1.813zM8.163 3.1c.25.338 2.038 2.863 3.7 5.763-4.675 1.25-8.8 1.225-9.25 1.225a10.217 10.217 0 0 1 5.55-6.988zM1.813 12v-.375c.438.013 5.325.063 10.313-1.425.288.563.563 1.138.813 1.713-.125.038-.25.088-.375.138-5.125 1.65-7.875 6.175-8.063 6.475A10.165 10.165 0 0 1 1.813 12zm3.625 8.125c.15-.25 2.275-4.35 7.65-6.188.025-.013.05-.013.075-.025 1.4 3.638 1.975 6.7 2.113 7.663A10.18 10.18 0 0 1 12 22.188c-3.5 0-6.55-1.85-8.563-4.063zm10.813 1.125c-.163-.925-.713-3.863-2.025-7.463 3.175-.5 5.925.325 6.263.425a10.2 10.2 0 0 1-4.238 7.038z"/></svg>',
                instagram: '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
                linkedin: '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                github: '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
                twitter: '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'
            };

            // ========== LOADER ==========
            var loaderWrapper = document.getElementById('loaderWrapper');

            function hideLoader() {
                if (loaderWrapper) {
                    loaderWrapper.classList.add('hidden');
                }
            }

            // Hide loader after content is ready
            window.addEventListener('load', function() {
                setTimeout(hideLoader, 1800);
            });

            // Fallback: hide loader after 3 seconds max
            setTimeout(hideLoader, 3000);

            // ========== LOAD DATA.JSON ==========
            fetch('assets/data.json')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    // Populate footer social icons
                    var footerSocial = document.getElementById('footerSocial');
                    if (footerSocial && data.social) {
                        footerSocial.innerHTML = '';
                        data.social.forEach(function(item) {
                            var a = document.createElement('a');
                            a.href = item.url;
                            a.className = 'social-link';
                            a.target = '_blank';
                            a.rel = 'noopener noreferrer';
                            a.setAttribute('aria-label', item.name);
                            a.innerHTML = socialIcons[item.icon] || '';
                            footerSocial.appendChild(a);
                        });
                    }

                    // Populate footer quick links
                    var footerQuickLinks = document.getElementById('footerQuickLinks');
                    if (footerQuickLinks && data.footer && data.footer.quickLinks) {
                        footerQuickLinks.innerHTML = '';
                        data.footer.quickLinks.forEach(function(item) {
                            var li = document.createElement('li');
                            li.innerHTML = '<a href="/' + item.page + '" data-nav><span class="material-symbols-rounded">chevron_right</span> ' + item.label + '</a>';
                            footerQuickLinks.appendChild(li);
                        });
                    }

                    // Populate footer service links
                    var footerServiceLinks = document.getElementById('footerServiceLinks');
                    if (footerServiceLinks && data.footer && data.footer.serviceLinks) {
                        footerServiceLinks.innerHTML = '';
                        data.footer.serviceLinks.forEach(function(item) {
                            var li = document.createElement('li');
                            li.innerHTML = '<a href="/' + item.page + '" data-nav><span class="material-symbols-rounded">chevron_right</span> ' + item.label + '</a>';
                            footerServiceLinks.appendChild(li);
                        });
                    }

                    // Populate footer contact
                    if (data.contact) {
                        var footerEmail = document.getElementById('footerEmail');
                        if (footerEmail && data.contact.email) footerEmail.textContent = data.contact.email;
                        var footerLocation = document.getElementById('footerLocation');
                        if (footerLocation && data.contact.location) footerLocation.innerHTML = data.contact.location + '<br>Remote Projects Welcome';
                    }

                    // Populate footer copyright
                    var footerCopyright = document.getElementById('footerCopyright');
                    if (footerCopyright && data.footer && data.footer.copyright) {
                        footerCopyright.innerHTML = '&copy; ' + data.footer.copyright + '. All rights reserved.';
                    }

                    // Populate contact page social section
                    var contactSocial = document.getElementById('contactSocialLinks');
                    if (contactSocial && data.social) {
                        contactSocial.innerHTML = '';
                        data.social.forEach(function(item) {
                            var a = document.createElement('a');
                            a.href = item.url;
                            a.className = 'contact-social-link';
                            a.target = '_blank';
                            a.rel = 'noopener noreferrer';
                            a.setAttribute('aria-label', item.name);
                            a.innerHTML = socialIcons[item.icon] + '<span>' + item.name + '</span>';
                            contactSocial.appendChild(a);
                        });
                    }
                })
                .catch(function() {
                    // Fallback: hardcode social links if data.json fails
                    var footerSocial = document.getElementById('footerSocial');
                    if (footerSocial) {
                        footerSocial.innerHTML =
                            '<a href="#" class="social-link" aria-label="Behance">' + socialIcons.behance + '</a>' +
                            '<a href="#" class="social-link" aria-label="Instagram">' + socialIcons.instagram + '</a>' +
                            '<a href="#" class="social-link" aria-label="LinkedIn">' + socialIcons.linkedin + '</a>';
                    }
                });

            // ========== PANELS & NAVIGATION ==========
            var overlay = document.getElementById('panelOverlay');
            var mobileNavPanel = document.getElementById('mobileNavPanel');
            var requestPanel = document.getElementById('requestPanel');
            var mobileMenuToggler = document.getElementById('mobileMenuToggler');
            var closeMobileNav = document.getElementById('closeMobileNav');
            var openRequestBtn = document.getElementById('openRequestBtn');
            var closeRequestPanel = document.getElementById('closeRequestPanel');
            var mobileRequestBtn = document.getElementById('mobileRequestBtn');
            var requestForm = document.getElementById('requestForm');
            var toastSuccess = document.getElementById('toastSuccess');
            var mainHeader = document.getElementById('mainHeader');
            var activePanel = null;

            function closeAllPanels() {
                if (mobileNavPanel) mobileNavPanel.classList.remove('active');
                if (requestPanel) requestPanel.classList.remove('active');
                if (overlay) overlay.classList.remove('show');
                if (mobileMenuToggler) mobileMenuToggler.classList.remove('active');
                activePanel = null;
                document.body.style.overflow = '';
            }

            function openPanel(panel) {
                closeAllPanels();
                if (panel) {
                    panel.classList.add('active');
                    if (overlay) overlay.classList.add('show');
                    activePanel = panel;
                    document.body.style.overflow = 'hidden';
                    if (panel === mobileNavPanel && mobileMenuToggler) {
                        mobileMenuToggler.classList.add('active');
                    }
                }
            }

            function toggleMobileNav() {
                if (activePanel === mobileNavPanel) {
                    closeAllPanels();
                } else {
                    openPanel(mobileNavPanel);
                }
            }

            function showToast() {
                var message = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'Request sent successfully!';
                if (toastSuccess) {
                    var iconSpan = toastSuccess.querySelector('span');
                    if (iconSpan) iconSpan.textContent = 'check_circle';
                    var textNode = toastSuccess.childNodes[1];
                    if (textNode) textNode.textContent = ' ' + message;
                    toastSuccess.classList.add('show');
                    setTimeout(function() {
                        toastSuccess.classList.remove('show');
                    }, 3000);
                }
            }

            // Header scroll effect
            var scrollTicking = false;
            window.addEventListener('scroll', function() {
                if (!scrollTicking) {
                    requestAnimationFrame(function() {
                        if (window.scrollY > 20) {
                            if (mainHeader) mainHeader.classList.add('header-scrolled');
                        } else {
                            if (mainHeader) mainHeader.classList.remove('header-scrolled');
                        }
                        scrollTicking = false;
                    });
                    scrollTicking = true;
                }
            });

            // Event listeners
            if (mobileMenuToggler) {
                mobileMenuToggler.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleMobileNav();
                });
            }

            if (closeMobileNav) closeMobileNav.addEventListener('click', closeAllPanels);
            if (closeRequestPanel) closeRequestPanel.addEventListener('click', closeAllPanels);

            if (openRequestBtn) {
                openRequestBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openPanel(requestPanel);
                });
            }

            if (mobileRequestBtn) {
                mobileRequestBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeAllPanels();
                    setTimeout(function() {
                        openPanel(requestPanel);
                    }, 150);
                });
            }

            if (overlay) overlay.addEventListener('click', closeAllPanels);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeAllPanels();
            });

            // Form submission
            if (requestForm) {
                requestForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var inputs = requestForm.querySelectorAll('input[required], textarea[required], select[required]');
                    var valid = true;
                    inputs.forEach(function(input) {
                        if (!input.value.trim()) {
                            valid = false;
                            input.style.borderColor = '#ff4444';
                            input.style.animation = 'shake 0.5s ease';
                            setTimeout(function() {
                                input.style.animation = '';
                            }, 500);
                        } else {
                            input.style.borderColor = '#2a2a2a';
                        }
                    });

                    if (!valid) return;

                    var submitBtn = requestForm.querySelector('.submit-request');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = 'Sending... <span class="material-symbols-rounded">hourglass_empty</span>';
                    }

                    var formData = {
                        name: requestForm.querySelector('[name="name"]').value.trim(),
                        email: requestForm.querySelector('[name="email"]').value.trim(),
                        service: requestForm.querySelector('[name="service"]').value,
                        message: requestForm.querySelector('[name="message"]').value.trim(),
                        budget: requestForm.querySelector('[name="budget"]') ? requestForm.querySelector('[name="budget"]').value.trim() : ''
                    };

                    var apiBase = window.API_BASE || '';
                    if (apiBase) {
                        fetch(apiBase + '/contact.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(formData)
                        })
                        .then(function(res) { return res.json(); })
                        .then(function() {
                            showToast('Request sent successfully!');
                            requestForm.reset();
                            closeAllPanels();
                        })
                        .catch(function() {
                            showToast('Request received! I\'ll get back to you soon.');
                            requestForm.reset();
                            closeAllPanels();
                        })
                        .finally(function() {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = 'Send Request <span class="material-symbols-rounded">arrow_forward</span>';
                            }
                        });
                    } else {
                        showToast('Request received! I\'ll get back to you soon.');
                        requestForm.reset();
                        closeAllPanels();
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = 'Send Request <span class="material-symbols-rounded">arrow_forward</span>';
                        }
                    }
                });
            }

            // Close mobile nav when a link is clicked
            var mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
            mobileNavLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    closeAllPanels();
                });
            });

            // ========== CONTACT FORM ==========
            var contactForm = document.getElementById('contactForm');
            var contactToast = document.getElementById('contactToast');

            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var inputs = contactForm.querySelectorAll('input[required], textarea[required], select[required]');
                    var valid = true;
                    inputs.forEach(function(input) {
                        if (!input.value.trim()) {
                            valid = false;
                            input.style.borderColor = '#ff4444';
                            input.style.animation = 'shake 0.5s ease';
                            setTimeout(function() {
                                input.style.animation = '';
                            }, 500);
                        } else {
                            input.style.borderColor = '#2a2a2a';
                        }
                    });

                    if (!valid) return;

                    var submitBtn = contactForm.querySelector('.submit-btn');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = 'Sending... <span class="material-symbols-rounded">hourglass_empty</span>';
                    }

                    var formData = {
                        name: contactForm.querySelector('[name="name"]').value.trim(),
                        email: contactForm.querySelector('[name="email"]').value.trim(),
                        subject: contactForm.querySelector('[name="subject"]').value.trim(),
                        message: contactForm.querySelector('[name="message"]').value.trim()
                    };

                    var apiBase = window.API_BASE || '';
                    if (apiBase) {
                        fetch(apiBase + '/contact.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(formData)
                        })
                        .then(function(res) { return res.json(); })
                        .then(function() {
                            if (contactToast) {
                                contactToast.classList.add('show');
                                setTimeout(function() { contactToast.classList.remove('show'); }, 3000);
                            }
                            contactForm.reset();
                        })
                        .catch(function() {
                            showToast('Something went wrong. Please try again.');
                        })
                        .finally(function() {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = 'Send Message <span class="material-symbols-rounded">arrow_forward</span>';
                            }
                        });
                    } else {
                        if (contactToast) {
                            contactToast.classList.add('show');
                            setTimeout(function() { contactToast.classList.remove('show'); }, 3000);
                        }
                        contactForm.reset();
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = 'Send Message <span class="material-symbols-rounded">arrow_forward</span>';
                        }
                    }
                });
            }

            // ========== NEWSLETTER FORM ==========
            var newsletterForm = document.getElementById('newsletterForm');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var emailInput = newsletterForm.querySelector('input[type="email"]');
                    if (!emailInput || !emailInput.value.trim()) return;

                    var apiBase = window.API_BASE || '';
                    if (apiBase) {
                        fetch(apiBase + '/newsletter.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: emailInput.value.trim() })
                        })
                        .then(function() {
                            showToast('Subscribed successfully!');
                            newsletterForm.reset();
                        })
                        .catch(function() {
                            showToast('Subscription failed. Try again.');
                        });
                    } else {
                        showToast('Subscribed successfully!');
                        newsletterForm.reset();
                    }
                });
            }

            // ========== FOOTER INTERACTIONS ==========
            // Ripple effect on social links
            document.addEventListener('click', function(e) {
                var link = e.target.closest('.social-link');
                if (link) {
                    e.preventDefault();
                    var ripple = document.createElement('span');
                    ripple.style.cssText = 'position:absolute;border-radius:50%;background:rgba(255,255,255,0.4);width:20px;height:20px;animation:ripple 0.6s ease-out;pointer-events:none;';
                    link.style.position = 'relative';
                    link.style.overflow = 'hidden';
                    link.appendChild(ripple);
                    setTimeout(function() {
                        ripple.remove();
                    }, 600);
                }
            });

            // Add ripple animation style dynamically
            var rippleStyle = document.createElement('style');
            rippleStyle.textContent = '@keyframes ripple { from { transform: scale(0); opacity: 1; } to { transform: scale(4); opacity: 0; } }';
            document.head.appendChild(rippleStyle);

            // ========== SCROLL ANIMATIONS ==========
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

            document.querySelectorAll('.anim-reveal').forEach(function(el) {
                observer.observe(el);
            });

            // ========== SERVICE EXPAND/COLLAPSE ==========
            document.querySelectorAll('.service-row-header').forEach(function(header) {
                header.addEventListener('click', function() {
                    var row = header.closest('.service-row');
                    var isActive = row.classList.contains('active');

                    // Close all others
                    document.querySelectorAll('.service-row.active').forEach(function(open) {
                        if (open !== row) open.classList.remove('active');
                    });

                    row.classList.toggle('active', !isActive);
                });
            });

            // ========== GALLERY ==========
            var galleryGrid = document.getElementById('galleryGrid');
            var galleryLoading = document.getElementById('galleryLoading');
            var galleryEmpty = document.getElementById('galleryEmpty');
            var lightbox = document.getElementById('lightbox');
            var lightboxImg = document.getElementById('lightboxImg');
            var lightboxTitle = document.getElementById('lightboxTitle');
            var lightboxDesc = document.getElementById('lightboxDesc');
            var lightboxClose = document.getElementById('lightboxClose');
            var lightboxPrev = document.getElementById('lightboxPrev');
            var lightboxNext = document.getElementById('lightboxNext');
            var currentGalleryImages = [];
            var currentLightboxIndex = 0;

            function renderGallery(images) {
                if (!galleryGrid) return;
                galleryGrid.innerHTML = '';

                if (!images || images.length === 0) {
                    if (galleryEmpty) galleryEmpty.style.display = 'block';
                    if (galleryLoading) galleryLoading.style.display = 'none';
                    return;
                }

                if (galleryEmpty) galleryEmpty.style.display = 'none';

                images.forEach(function(img, index) {
                    var item = document.createElement('div');
                    item.className = 'gallery-item anim-reveal';
                    item.setAttribute('data-category', img.category || '');
                    var resolvedSrc = resolveImageUrl(img.src);
                    item.innerHTML =
                        '<img src="' + resolvedSrc + '" alt="' + (img.title || 'Gallery image') + '" loading="lazy">' +
                        '<div class="gallery-overlay">' +
                            '<span class="gallery-overlay-title">' + (img.title || '') + '</span>' +
                            '<span class="gallery-overlay-desc">' + (img.description || '') + '</span>' +
                            '<span class="gallery-overlay-icon"><span class="material-symbols-rounded">open_in_full</span></span>' +
                        '</div>';
                    item.addEventListener('click', function() {
                        openLightbox(images, index);
                    });
                    galleryGrid.appendChild(item);
                });

                if (galleryLoading) galleryLoading.style.display = 'none';

                document.querySelectorAll('.gallery-grid .anim-reveal').forEach(function(el) {
                    observer.observe(el);
                });
            }

            function openLightbox(images, index) {
                currentGalleryImages = images;
                currentLightboxIndex = index;
                updateLightbox();
                if (lightbox) {
                    lightbox.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            }

            function updateLightbox() {
                var img = currentGalleryImages[currentLightboxIndex];
                if (!img) return;
                if (lightboxImg) { lightboxImg.src = resolveImageUrl(img.src); lightboxImg.alt = img.title || ''; }
                if (lightboxTitle) lightboxTitle.textContent = img.title || '';
                if (lightboxDesc) lightboxDesc.textContent = img.description || '';
            }

            function closeLightbox() {
                if (lightbox) {
                    lightbox.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }

            if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
            if (lightbox) lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) closeLightbox();
            });
            if (lightboxPrev) lightboxPrev.addEventListener('click', function(e) {
                e.stopPropagation();
                currentLightboxIndex = (currentLightboxIndex - 1 + currentGalleryImages.length) % currentGalleryImages.length;
                updateLightbox();
            });
            if (lightboxNext) lightboxNext.addEventListener('click', function(e) {
                e.stopPropagation();
                currentLightboxIndex = (currentLightboxIndex + 1) % currentGalleryImages.length;
                updateLightbox();
            });
            document.addEventListener('keydown', function(e) {
                if (!lightbox || !lightbox.classList.contains('active')) return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') { currentLightboxIndex = (currentLightboxIndex - 1 + currentGalleryImages.length) % currentGalleryImages.length; updateLightbox(); }
                if (e.key === 'ArrowRight') { currentLightboxIndex = (currentLightboxIndex + 1) % currentGalleryImages.length; updateLightbox(); }
            });

            var galleryGridEl = document.getElementById('galleryGrid');
            if (galleryGridEl) {
                var apiBase = window.API_BASE || '';
                var galleryUrl = apiBase ? apiBase + '/gallery.php' : 'api/images.json';

                fetch(galleryUrl)
                    .then(function(res) {
                        if (!res.ok) throw new Error('Failed to load gallery');
                        return res.json();
                    })
                    .then(function(data) {
                        var images = data.images || data || [];
                        currentGalleryImages = images;
                        renderGallery(images);
                    })
                    .catch(function() {
                        fetch('api/images.json')
                            .then(function(res) { return res.json(); })
                            .then(function(data) {
                                var images = data.images || [];
                                currentGalleryImages = images;
                                renderGallery(images);
                            })
                            .catch(function() {
                                if (galleryLoading) galleryLoading.innerHTML = '<p>Failed to load gallery.</p>';
                            });
                    });
            }

            // Gallery filter buttons
            document.querySelectorAll('.gallery-filter-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.gallery-filter-btn').forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    var filter = btn.getAttribute('data-filter');
                    var items = document.querySelectorAll('.gallery-grid .gallery-item');
                    items.forEach(function(item) {
                        if (filter === 'all' || item.getAttribute('data-category') === filter) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });

            // ========== PORTFOLIO API LOADING ==========
            var portfolioGrid = document.getElementById('portfolioGrid');
            var portfolioLoading = document.getElementById('portfolioLoading');
            var portfolioEmpty = document.getElementById('portfolioEmpty');
            if (portfolioGrid) {
                var apiBase = window.API_BASE || '';
                var portfolioUrl = apiBase ? apiBase + '/portfolio.php' : '';

                if (portfolioUrl) {
                    fetch(portfolioUrl)
                        .then(function(res) {
                            if (!res.ok) throw new Error('Failed');
                            return res.json();
                        })
                        .then(function(data) {
                            var projects = data.projects || [];
                            if (projects.length > 0) {
                                renderPortfolio(projects);
                            } else {
                                if (portfolioLoading) portfolioLoading.style.display = 'none';
                                if (portfolioEmpty) portfolioEmpty.style.display = 'block';
                            }
                        })
                        .catch(function() {
                            if (portfolioLoading) portfolioLoading.style.display = 'none';
                            if (portfolioEmpty) portfolioEmpty.style.display = 'block';
                        });
                } else {
                    if (portfolioLoading) portfolioLoading.style.display = 'none';
                    if (portfolioEmpty) portfolioEmpty.style.display = 'block';
                }

                function renderPortfolio(projects) {
                    var categoryLabels = { web: 'Web Dev', branding: 'Branding', uiux: 'UI/UX', graphic: 'Graphic' };
                    portfolioGrid.innerHTML = '';
                    projects.forEach(function(p) {
                        var card = document.createElement('div');
                        card.className = 'portfolio-card anim-reveal';
                        card.setAttribute('data-category', p.category || '');
                        var tags = (p.tags || []).map(function(t) { return '<span>' + t + '</span>'; }).join('');
                        var resolvedImg = resolveImageUrl(p.image);
                        card.innerHTML =
                            '<div class="portfolio-card-image">' +
                                '<img src="' + resolvedImg + '" alt="' + (p.title || '') + '" loading="lazy">' +
                                '<div class="portfolio-card-badge">' + (categoryLabels[p.category] || p.category) + '</div>' +
                            '</div>' +
                            '<div class="portfolio-card-body">' +
                                '<h3>' + (p.title || '') + '</h3>' +
                                '<p>' + (p.description || '') + '</p>' +
                                '<div class="portfolio-card-tags">' + tags + '</div>' +
                            '</div>';
                        if (p.project_url) {
                            card.style.cursor = 'pointer';
                            card.addEventListener('click', function() { window.open(p.project_url, '_blank'); });
                        }
                        portfolioGrid.appendChild(card);
                    });
                    if (portfolioLoading) portfolioLoading.style.display = 'none';
                    document.querySelectorAll('.portfolio-grid .anim-reveal').forEach(function(el) {
                        observer.observe(el);
                    });
                }
            }

            // ========== PORTFOLIO FILTER ==========
            document.querySelectorAll('.portfolio-filter-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.portfolio-filter-btn').forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    var filter = btn.getAttribute('data-filter');
                    var items = document.querySelectorAll('.portfolio-grid .portfolio-card');
                    items.forEach(function(item) {
                        if (filter === 'all' || item.getAttribute('data-category') === filter) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });

            // ========== PAGE CONTENT LOADING ==========
            (function loadPageContent() {
                var params = new URLSearchParams(window.location.search);
                var page = params.get('page') || 'home';
                var validPages = ['home', 'about', 'service', 'contact'];
                if (validPages.indexOf(page) === -1) return;

                var apiBase = window.API_BASE || '';
                if (!apiBase) return;

                fetch(apiBase + '/page-content.php?page=' + page)
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (!data.success || !data.content) return;
                        var c = data.content;
                        if (page === 'home') applyHomeContent(c);
                        else if (page === 'about') applyAboutContent(c);
                        else if (page === 'service') applyServiceContent(c);
                        else if (page === 'contact') applyContactContent(c);
                    })
                    .catch(function() {});
            })();

            function setText(id, val) {
                var el = document.getElementById(id);
                if (el && val !== undefined && val !== null) el.textContent = val;
            }
            function setHTML(id, val) {
                var el = document.getElementById(id);
                if (el && val !== undefined && val !== null) el.innerHTML = val;
            }
            function setAttr(id, attr, val) {
                var el = document.getElementById(id);
                if (el && val) el.setAttribute(attr, val);
            }

            function applyHomeContent(c) {
                setHTML('home-hero-title', c.hero_title);
                setText('home-hero-subtitle', c.hero_subtitle);
                setText('home-hero-cta', c.hero_cta);
                setAttr('home-hero-cta', 'href', c.hero_cta_link);
                setText('home-services-label', c.services_label);
                setHTML('home-services-label', '<span class="material-symbols-rounded">design_services</span> ' + (c.services_label || ''));
                setHTML('home-services-heading', c.services_heading);
                if (c.services && c.services.length) {
                    var grid = document.getElementById('home-services-grid');
                    if (grid) {
                        grid.innerHTML = '';
                        c.services.forEach(function(s) {
                            var card = document.createElement('div');
                            card.className = 'home-service-card anim-reveal';
                            card.innerHTML = '<span class="material-symbols-rounded">' + (s.icon || '') + '</span><h4>' + (s.title || '') + '</h4><p>' + (s.desc || '') + '</p>';
                            grid.appendChild(card);
                        });
                        grid.querySelectorAll('.anim-reveal').forEach(function(el) { observer.observe(el); });
                    }
                }
                setText('home-services-btn', c.services_btn);
                setAttr('home-services-btn', 'href', c.services_btn_link);
                setText('home-about-label', c.about_label);
                setHTML('home-about-label', '<span class="material-symbols-rounded">person</span> ' + (c.about_label || ''));
                setHTML('home-about-heading', c.about_heading);
                setText('home-about-text', c.about_text);
                setText('home-about-btn', c.about_btn);
                if (c.about_stats && c.about_stats.length) {
                    var statsEl = document.getElementById('home-about-stats');
                    if (statsEl) {
                        statsEl.innerHTML = '';
                        c.about_stats.forEach(function(s) {
                            var div = document.createElement('div');
                            div.className = 'home-stat';
                            div.innerHTML = '<span class="home-stat-number">' + (s.number || '') + '</span><span class="home-stat-label">' + (s.label || '') + '</span>';
                            statsEl.appendChild(div);
                        });
                    }
                }
                setText('home-cta-heading', c.cta_heading);
                setText('home-cta-text', c.cta_text);
                setText('home-cta-btn1', c.cta_btn1);
                setAttr('home-cta-btn1', 'href', c.cta_btn1_link);
                setText('home-cta-btn2', c.cta_btn2);
                setAttr('home-cta-btn2', 'href', c.cta_btn2_link);
            }

            function applyAboutContent(c) {
                setText('about-story-label', c.story_label);
                setHTML('about-story-label', '<span class="material-symbols-rounded">auto_stories</span> ' + (c.story_label || ''));
                setHTML('about-story-heading', c.story_heading);
                setText('about-story-p1', c.story_p1);
                setText('about-story-p2', c.story_p2);
                setAttr('about-story-image', 'src', c.story_image);
                if (c.story_stats && c.story_stats.length) {
                    var statsEl = document.getElementById('about-story-stats');
                    if (statsEl) {
                        statsEl.innerHTML = '';
                        c.story_stats.forEach(function(s) {
                            var div = document.createElement('div');
                            div.className = 'stat';
                            div.innerHTML = '<span class="stat-number">' + (s.number || '') + '</span><span class="stat-label">' + (s.label || '') + '</span>';
                            statsEl.appendChild(div);
                        });
                    }
                }
                setText('about-skills-label', c.skills_label);
                setHTML('about-skills-label', '<span class="material-symbols-rounded">psychology</span> ' + (c.skills_label || ''));
                setHTML('about-skills-heading', c.skills_heading);
                if (c.skills && c.skills.length) {
                    var grid = document.getElementById('about-skills-grid');
                    if (grid) {
                        grid.innerHTML = '';
                        c.skills.forEach(function(sk) {
                            var tags = (sk.tags || []).map(function(t) { return '<span>' + t + '</span>'; }).join('');
                            var card = document.createElement('div');
                            card.className = 'skill-card anim-reveal';
                            card.innerHTML = '<div class="skill-icon"><span class="material-symbols-rounded">' + (sk.icon || '') + '</span></div><h3>' + (sk.title || '') + '</h3><p>' + (sk.desc || '') + '</p><div class="skill-tags">' + tags + '</div>';
                            grid.appendChild(card);
                        });
                        grid.querySelectorAll('.anim-reveal').forEach(function(el) { observer.observe(el); });
                    }
                }
                setText('about-gallery-label', c.gallery_label);
                setHTML('about-gallery-label', '<span class="material-symbols-rounded">photo_library</span> ' + (c.gallery_label || ''));
                setHTML('about-gallery-heading', c.gallery_heading);
                setText('about-philosophy-quote', c.philosophy_quote);
                setText('about-philosophy-author', c.philosophy_author);
                setText('about-philosophy-role', c.philosophy_role);
                setText('about-cta-heading', c.cta_heading);
                setText('about-cta-text', c.cta_text);
                setText('about-cta-btn1', c.cta_btn1);
                setText('about-cta-btn2', c.cta_btn2);
            }

            function applyServiceContent(c) {
                setText('service-hero-label', c.hero_label);
                setHTML('service-hero-label', '<span class="material-symbols-rounded">design_services</span> ' + (c.hero_label || ''));
                setHTML('service-hero-heading', c.hero_heading);
                setText('service-hero-text', c.hero_text);
                if (c.services && c.services.length) {
                    var container = document.getElementById('service-list-container');
                    if (container) {
                        container.innerHTML = '';
                        c.services.forEach(function(sv) {
                            var items = (sv.items || []).map(function(i) { return '<li>' + i + '</li>'; }).join('');
                            var tools = (sv.tools || []).map(function(t) { return '<span>' + t + '</span>'; }).join('');
                            var row = document.createElement('div');
                            row.className = 'service-row anim-reveal';
                            row.innerHTML =
                                '<div class="service-row-header">' +
                                    '<div class="service-row-icon"><span class="material-symbols-rounded">' + (sv.icon || '') + '</span></div>' +
                                    '<div class="service-row-info"><h3>' + (sv.name || '') + '</h3><p>' + (sv.desc || '') + '</p></div>' +
                                    '<button class="service-expand-btn" aria-label="Expand details"><span class="material-symbols-rounded">expand_more</span></button>' +
                                '</div>' +
                                '<div class="service-row-detail"><div class="service-detail-grid">' +
                                    '<div class="service-detail-text"><h4>What\'s Included</h4><ul>' + items + '</ul></div>' +
                                    '<div class="service-detail-text"><h4>Tools</h4><div class="service-tags">' + tools + '</div></div>' +
                                '</div>' +
                                '<div class="service-detail-footer">' +
                                    '<span class="service-delivery">Typical delivery: ' + (sv.delivery || '') + '</span>' +
                                    '<a href="/contact" data-nav class="service-cta-btn">Start a Project <span class="material-symbols-rounded">arrow_forward</span></a>' +
                                '</div></div>';
                            container.appendChild(row);
                        });
                        container.querySelectorAll('.anim-reveal').forEach(function(el) { observer.observe(el); });
                        container.querySelectorAll('.service-row-header').forEach(function(header) {
                            header.addEventListener('click', function() {
                                var row = header.closest('.service-row');
                                var isActive = row.classList.contains('active');
                                container.querySelectorAll('.service-row.active').forEach(function(open) { if (open !== row) open.classList.remove('active'); });
                                row.classList.toggle('active', !isActive);
                            });
                        });
                    }
                }
                setText('service-process-label', c.process_label);
                setHTML('service-process-label', '<span class="material-symbols-rounded">route</span> ' + (c.process_label || ''));
                setHTML('service-process-heading', c.process_heading);
                if (c.process_steps && c.process_steps.length) {
                    var pgrid = document.getElementById('service-process-grid');
                    if (pgrid) {
                        pgrid.innerHTML = '';
                        c.process_steps.forEach(function(st) {
                            var div = document.createElement('div');
                            div.className = 'process-step anim-reveal';
                            div.innerHTML = '<div class="process-number">' + (st.number || '') + '</div><h4>' + (st.title || '') + '</h4><p>' + (st.desc || '') + '</p>';
                            pgrid.appendChild(div);
                        });
                        pgrid.querySelectorAll('.anim-reveal').forEach(function(el) { observer.observe(el); });
                    }
                }
                setText('service-pricing-heading', c.pricing_heading);
                setText('service-pricing-text', c.pricing_text);
                setText('service-pricing-btn', c.pricing_btn);
            }

            function applyContactContent(c) {
                setText('contact-hero-label', c.hero_label);
                setHTML('contact-hero-label', '<span class="material-symbols-rounded">mail</span> ' + (c.hero_label || ''));
                setHTML('contact-hero-heading', c.hero_heading);
                setText('contact-hero-text', c.hero_text);
                if (c.info_cards && c.info_cards.length) {
                    var cardsEl = document.getElementById('contact-info-cards');
                    if (cardsEl) {
                        var nlCard = cardsEl.querySelector('.newsletter-card');
                        cardsEl.querySelectorAll('.info-card').forEach(function(el) { el.remove(); });
                        c.info_cards.forEach(function(cd) {
                            var div = document.createElement('div');
                            div.className = 'info-card';
                            div.innerHTML = '<div class="info-icon"><span class="material-symbols-rounded">' + (cd.icon || '') + '</span></div><div class="info-content"><h4>' + (cd.title || '') + '</h4><p>' + (cd.value || '') + '</p></div>';
                            cardsEl.insertBefore(div, nlCard);
                        });
                    }
                }
                setText('contact-newsletter-title', c.newsletter_title);
                setHTML('contact-newsletter-title', '<span class="material-symbols-rounded">newsmode</span> ' + (c.newsletter_title || ''));
                setText('contact-newsletter-text', c.newsletter_text);
                setText('contact-social-label', c.social_label);
                setHTML('contact-social-heading', c.social_heading);
                setText('contact-social-text', c.social_text);
            }

        })();
