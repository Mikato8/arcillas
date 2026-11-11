/* ═══════════════════════════════════════════════════════════════════════
   Arcillas MX · JavaScript propio (sin dependencias)
   - Header con sombra al hacer scroll y menú móvil
   - Envío automático de selects de ordenamiento
   - Comparador de materiales (localStorage, máx. 4)
   - Calculadoras: envío JSON al endpoint y render del resultado
   - Panel: filas de propiedades dinámicas y slug automático
   ═══════════════════════════════════════════════════════════════════════ */
(() => {
  'use strict';

  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  // ── Header sticky ────────────────────────────────────────────────────
  const header = $('#sitio-header');
  if (header) {
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  // ── Menú móvil (tres barras) con buscador integrado ──────────────────
  const menuBtn = $('[data-menu-toggle]');
  const menu = $('#nav-movil');
  const btnBuscarMovil = $('[data-abrir-busqueda]');

  const abrirMenu = (enfocarBuscador = false) => {
    if (!menu || !menuBtn) return;
    menu.classList.add('is-abierto');
    menuBtn.setAttribute('aria-expanded', 'true');
    menuBtn.setAttribute('aria-label', 'Cerrar menú');
    document.body.classList.add('menu-abierto');
    if (enfocarBuscador) {
      const input = $('#busqueda-movil');
      if (input) {
        setTimeout(() => {
          input.focus({ preventScroll: true });
          input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 120);
      }
    }
  };

  const cerrarMenu = () => {
    if (!menu || !menuBtn) return;
    menu.classList.remove('is-abierto');
    menuBtn.setAttribute('aria-expanded', 'false');
    menuBtn.setAttribute('aria-label', 'Abrir menú');
    document.body.classList.remove('menu-abierto');
  };

  if (menuBtn && menu) {
    menuBtn.addEventListener('click', () => {
      menu.classList.contains('is-abierto') ? cerrarMenu() : abrirMenu(false);
    });
    btnBuscarMovil?.addEventListener('click', () => abrirMenu(true));
    $$('[data-menu-link]').forEach((enlace) => enlace.addEventListener('click', cerrarMenu));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') cerrarMenu();
    });
    document.addEventListener('click', (event) => {
      if (!menu.classList.contains('is-abierto')) return;
      if (menu.contains(event.target) || menuBtn.contains(event.target) || btnBuscarMovil?.contains(event.target)) return;
      cerrarMenu();
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 860) cerrarMenu();
    });
  }

  // ── Filtros colapsables en el catálogo móvil ────────────────────────
  const layoutBusqueda = $('.layout-busqueda');
  const toggleFiltros = $('[data-filtros-toggle]');
  if (layoutBusqueda && toggleFiltros) {
    if (window.matchMedia('(max-width: 860px)').matches) {
      layoutBusqueda.classList.add('filtros-colapsados');
    }
    toggleFiltros.addEventListener('click', () => {
      const abierto = layoutBusqueda.classList.toggle('filtros-abiertos');
      toggleFiltros.setAttribute('aria-expanded', String(abierto));
      if (abierto) {
        const panel = $('#panel-filtros');
        panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }

  // ── Barra fija de conversión en fichas (móvil) ──────────────────────
  if ($('.cta-movil')) document.body.classList.add('tiene-cta-movil');

  // ── Selects que se envían solos ──────────────────────────────────────
  $$('[data-auto-enviar]').forEach((select) => {
    select.addEventListener('change', () => select.form?.submit());
  });

  // ── Comparador ───────────────────────────────────────────────────────
  const CLAVE_COMPARAR = 'arcillas_comparar_v1';
  const leerComparar = () => {
    try {
      const data = JSON.parse(localStorage.getItem(CLAVE_COMPARAR) || '[]');
      return Array.isArray(data) ? data.filter((i) => i && i.slug) : [];
    } catch (e) {
      return [];
    }
  };
  const guardarComparar = (items) => {
    try { localStorage.setItem(CLAVE_COMPARAR, JSON.stringify(items)); } catch (e) { /* modo privado */ }
  };

  const barra = $('#barra-comparar');
  const chips = $('#comparar-chips');
  const irComparar = $('#comparar-ir');

  const pintarComparar = () => {
    if (!barra || !chips) return;
    const items = leerComparar();
    chips.innerHTML = '';
    items.forEach((item) => {
      const chip = document.createElement('span');
      chip.className = 'chip';
      chip.textContent = item.nombre;
      const quitar = document.createElement('button');
      quitar.type = 'button';
      quitar.setAttribute('aria-label', 'Quitar ' + item.nombre);
      quitar.textContent = '×';
      quitar.addEventListener('click', () => {
        guardarComparar(leerComparar().filter((i) => i.slug !== item.slug));
        pintarComparar();
        sincronizarBotones();
      });
      chip.appendChild(quitar);
      chips.appendChild(chip);
    });
    barra.classList.toggle('oculto', items.length === 0);
    if (irComparar) {
      const slugs = items.map((i) => encodeURIComponent(i.slug)).join(',');
      irComparar.href = (irComparar.dataset.base || '/comparar') + (slugs ? '?ids=' + slugs : '');
    }
  };

  const sincronizarBotones = () => {
    const slugs = leerComparar().map((i) => i.slug);
    $$('[data-comparar]').forEach((btn) => {
      const activo = slugs.includes(btn.dataset.comparar);
      btn.classList.toggle('is-comparando', activo);
      btn.textContent = activo ? '✓ En comparación' : 'Comparar';
    });
  };

  $$('[data-comparar]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const slug = btn.dataset.comparar;
      let items = leerComparar();
      if (items.some((i) => i.slug === slug)) {
        items = items.filter((i) => i.slug !== slug);
      } else {
        if (items.length >= 4) {
          alert('Puedes comparar hasta 4 materiales. Quita uno de la barra inferior.');
          return;
        }
        items.push({ slug, nombre: btn.dataset.nombre || slug });
      }
      guardarComparar(items);
      pintarComparar();
      sincronizarBotones();
    });
  });

  const limpiar = $('[data-comparar-limpiar]');
  if (limpiar) {
    limpiar.addEventListener('click', () => {
      guardarComparar([]);
      pintarComparar();
      sincronizarBotones();
    });
  }

  if (barra) {
    if (irComparar && !irComparar.dataset.base) irComparar.dataset.base = irComparar.getAttribute('href') || '/comparar';
    pintarComparar();
    sincronizarBotones();
  }

  // ── Calculadoras ─────────────────────────────────────────────────────
  const contenedorCalc = $('[data-calculadora]');
  if (contenedorCalc) {
    const form = $('#form-calculadora');
    const salida = $('#calc-salida');
    const vacio = $('#calc-vacio');
    const lista = $('#calc-lista');
    const resumen = $('#calc-resumen');
    const advertencias = $('#calc-advertencias');
    const formulas = $('#calc-formulas');
    const formulasLista = $('#calc-formulas-lista');
    const errorBox = $('#calc-error');
    const estado = $('#calc-estado');
    const boton = $('#boton-calcular');

    const escapeHtml = (texto) => String(texto).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const filaResultado = (fila) => {
      const bruto = fila.valor ?? fila.resultado;
      const valor = (bruto === null || bruto === undefined || bruto === '') ? '—' : bruto;
      const unidad = fila.unidad ? ' ' + fila.unidad : '';
      return '<div class="resultado-fila' + (fila.destacado ? ' destacado' : '') + '">'
        + '<span>' + escapeHtml(fila.etiqueta ?? fila.nombre ?? '') + '</span>'
        + '<strong>' + escapeHtml(String(valor)) + escapeHtml(unidad) + '</strong>'
        + '</div>';
    };

    const render = (data) => {
      const resultado = data.resultado || data;
      const filas = resultado.resultados || resultado.filas || [];
      lista.innerHTML = filas.map(filaResultado).join('');
      resumen.textContent = resultado.resumen || resultado.titulo || '';
      if (resultado.detalle && resultado.detalle.length) {
        const existente = lista.querySelector('[data-detalle]');
        if (existente) existente.remove();
        const detalle = document.createElement('details');
        detalle.className = 'formulas';
        detalle.setAttribute('data-detalle', '');
        detalle.innerHTML = '<summary>Detalle del cálculo</summary><div class="resultado-lista">'
          + resultado.detalle.map((f) => '<div class="resultado-fila"><span>' + escapeHtml(f.etiqueta ?? '') + '</span><strong>'
              + escapeHtml(String((f.valor === null || f.valor === undefined || f.valor === '') ? '—' : f.valor))
              + (f.unidad ? ' ' + escapeHtml(f.unidad) : '') + '</strong></div>').join('')
          + '</div>';
        lista.appendChild(detalle);
      }

      const avisos = resultado.advertencias || resultado.avisos || [];
      advertencias.innerHTML = avisos.length
        ? '<strong>Advertencias</strong><ul>' + avisos.map((a) => '<li>' + escapeHtml(a) + '</li>').join('') + '</ul>'
        : '';
      advertencias.classList.toggle('oculto', avisos.length === 0);

      const listaFormulas = resultado.formulas || [];
      formulasLista.innerHTML = listaFormulas.map((f) => '<code>' + escapeHtml(f) + '</code>').join('');
      formulas.classList.toggle('oculto', listaFormulas.length === 0);

      vacio.classList.add('oculto');
      salida.classList.remove('oculto');
      salida.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      errorBox.classList.add('oculto');
      const token = form.querySelector('input[name="_csrf"]')?.value || '';
      const datos = {};
      new FormData(form).forEach((valor, clave) => {
        if (clave === '_csrf') return;
        if (clave.endsWith('[]')) {
          const nombre = clave.slice(0, -2);
          (datos[nombre] = datos[nombre] || []).push(valor);
        } else if (clave.includes('[')) {
          const m = clave.match(/^([^\[]+)\[([^\]]+)\](\[([^\]]+)\])?/);
          if (m) {
            const base = m[1];
            datos[base] = datos[base] || {};
            if (m[4]) {
              datos[base][m[2]] = datos[base][m[2]] || {};
              datos[base][m[2]][m[4]] = valor;
            } else {
              datos[base][m[2]] = valor;
            }
          }
        } else {
          datos[clave] = valor;
        }
      });

      boton.disabled = true;
      estado.textContent = 'Calculando…';
      try {
        const respuesta = await fetch(contenedorCalc.dataset.endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': token },
          body: JSON.stringify(datos),
        });
        const json = await respuesta.json();
        if (!respuesta.ok || json.ok === false) {
          throw new Error(json.error || 'No pudimos calcular con esos datos.');
        }
        render(json);
        estado.textContent = 'Listo.';
      } catch (error) {
        errorBox.textContent = error.message;
        errorBox.classList.remove('oculto');
        estado.textContent = '';
      } finally {
        boton.disabled = false;
      }
    });

    // Cargar un ejemplo válido en el formulario.
    const botonEjemplo = $('#cargar-ejemplo');
    if (botonEjemplo) {
      botonEjemplo.addEventListener('click', () => {
        let ejemplo = {};
        try {
          ejemplo = JSON.parse(botonEjemplo.dataset.ejemplo || '{}');
        } catch (e) {
          ejemplo = {};
        }
        Object.entries(ejemplo).forEach(([clave, valor]) => {
          const input = form.querySelector('[name="' + clave + '"]');
          if (input) input.value = valor;
        });
        if (estado) estado.textContent = 'Ejemplo cargado. Presiona Calcular.';
        salida.classList.add('oculto');
        vacio.classList.remove('oculto');
      });
    }
  }

  // ── Admin: filas de propiedades y slug ───────────────────────────────
  const plantillaProp = $('#prop-plantilla');
  const listaProps = $('#props-lista');
  if (plantillaProp && listaProps) {
    let siguiente = 1000;
    $('[data-agregar-prop]')?.addEventListener('click', () => {
      const html = plantillaProp.innerHTML.replace(/__IDX__/g, String(siguiente++));
      const envoltorio = document.createElement('div');
      envoltorio.innerHTML = html.trim();
      listaProps.appendChild(envoltorio.firstElementChild);
    });
    listaProps.addEventListener('click', (event) => {
      const botonQuitar = event.target.closest('[data-quitar-prop]');
      if (botonQuitar) botonQuitar.closest('[data-prop-fila]')?.remove();
    });
  }

  const nombre = $('#nombre');
  const slug = $('#slug');
  if (nombre && slug) {
    let manual = slug.value.trim() !== '';
    slug.addEventListener('input', () => { manual = slug.value.trim() !== ''; });
    nombre.addEventListener('input', () => {
      if (manual) return;
      slug.value = nombre.value
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    });
  }
})();
