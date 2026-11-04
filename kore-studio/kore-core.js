/* =====================================================================
   KORE Studio — NÚCLEO COMPARTIDO DE LAS PÁGINAS (login, registro, agendamiento, CEO)
   Aquí se configura la conexión a la base de datos (Firebase). Se edita UNA sola vez.
   ===================================================================== */
(function (global) {
  'use strict';

  // ---------------------------------------------------------------
  //  CONFIGURACIÓN  (pega aquí los datos de tu proyecto de Firebase)
  //  Firebase → ⚙ Configuración del proyecto → General → "Tus apps" → apiKey / projectId
  //  Deben ser los MISMOS que usa la app del celular.
  // ---------------------------------------------------------------
  const CONFIG = {
    apiKey: 'AIzaSyBp08DEPElNPsHFWQxbhbd2cVPjJWmY6xU',
    projectId: 'kore-systems-web',
    emailDomain: 'kore-app.com',      // el "usuario" se convierte en usuario@kore-app.com (interno)
    recoveryUrl: '/api/recuperar',    // servidor de recuperación (server.js). Si las páginas no se sirven desde él,
                                      // pon la dirección completa, p. ej. 'https://tu-servidor.com/api/recuperar'
    pages: { login: 'login.html', register: 'registro.html', agenda: 'agendamiento.html', ceo: 'ceo.html' },
  };

  const AUTH_URL = 'https://identitytoolkit.googleapis.com/v1/accounts';
  const TOKEN_URL = 'https://securetoken.googleapis.com/v1/token';
  const DB = () => `https://firestore.googleapis.com/v1/projects/${CONFIG.projectId}/databases/(default)/documents`;
  const isConfigured = () => !/^TU_/.test(CONFIG.apiKey) && !/^TU_/.test(CONFIG.projectId);

  // ---------------------------------------------------------------
  //  Utilidades y errores
  // ---------------------------------------------------------------
  class ApiError extends Error {
    constructor(code, message, status) { super(message); this.code = code; this.status = status; }
  }

  async function request(url, options) {
    let res;
    try { res = await fetch(url, options); }
    catch (e) { throw new ApiError('NETWORK', 'Sin conexión con el servidor. Revisa tu internet e inténtalo de nuevo.', 0); }
    let data = null;
    try { data = await res.json(); } catch (e) { data = null; }
    if (!res.ok) {
      const raw = (data && data.error && (data.error.message || data.error.status || data.error)) || 'UNKNOWN';
      throw new ApiError(String(raw).split(/[\s:]/)[0], String(raw), res.status);
    }
    return data;
  }

  function friendlyError(e) {
    const c = (e && e.code) || '';
    if (c === 'NETWORK' || c === 'NOT_ADMIN' || c === 'SERVER') return e.message;
    if (c === 'EMAIL_EXISTS') return 'Ese nombre de usuario ya está en uso. Elige otro.';
    if (['INVALID_LOGIN_CREDENTIALS', 'EMAIL_NOT_FOUND', 'INVALID_PASSWORD'].includes(c)) return 'Usuario o contraseña incorrectos.';
    if (c === 'WEAK_PASSWORD') return 'La contraseña debe tener al menos 6 caracteres.';
    if (c === 'TOO_MANY_ATTEMPTS_TRY_LATER') return 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.';
    if (c === 'USER_DISABLED') return 'Esta cuenta fue deshabilitada por la administración.';
    if (c === 'OPERATION_NOT_ALLOWED') return 'El acceso con correo/contraseña no está activado en Firebase (Authentication → Método de acceso).';
    if (e && e.status === 403) return 'La base de datos rechazó la operación. Revisa que hayas publicado las reglas de seguridad.';
    return 'No se pudo completar la operación (' + ((e && e.message) || 'error desconocido') + ').';
  }

  const cleanUsername = (u) => String(u || '').trim().toLowerCase().replace(/^@/, '');
  const validUsername = (u) => u.length >= 3 && u.length <= 20 && /^[a-z0-9]+([._][a-z0-9]+)*$/.test(u);
  const toEmail = (id) => {
    const v = String(id || '').trim().toLowerCase().replace(/^@/, '');
    return v.includes('@') ? v : v + '@' + CONFIG.emailDomain; // acepta "usuario" o correo completo
  };

  // normaliza texto para comparar datos de recuperación (sin acentos, minúsculas, espacios simples)
  const norm = (s) => String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
  async function sha256(text) {
    const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
    return Array.from(new Uint8Array(buf)).map((b) => b.toString(16).padStart(2, '0')).join('');
  }

  const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const authCall = (endpoint, body) => request(`${AUTH_URL}:${endpoint}?key=${CONFIG.apiKey}`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body),
  });

  function toSession(d, username) {
    const expires = Number(d.expiresIn || d.expires_in || 3600);
    return {
      uid: d.localId || d.user_id,
      idToken: d.idToken || d.id_token,
      refreshToken: d.refreshToken || d.refresh_token,
      expiresAt: Date.now() + (expires - 60) * 1000,
      username,
    };
  }

  function parseUserDoc(doc) {
    const f = (doc && doc.fields) || {};
    const str = (k) => (f[k] && f[k].stringValue) || '';
    return {
      uid: String(doc.name || '').split('/').pop(),
      name: str('name'),
      username: str('username'),
      approved: !!(f.approved && f.approved.booleanValue),
      createdAt: str('createdAt'),
    };
  }

  // ---------------------------------------------------------------
  //  Cuenta con sesión propia (cada sección guarda la suya: clientes / CEO)
  // ---------------------------------------------------------------
  function account(kind) {
    const KEY = kind === 'admin' ? 'kore_web_admin_session' : 'kore_web_session';
    const PROFILE_KEY = kind === 'admin' ? 'kore_web_admin_profile' : 'kore_web_profile';

    const getSession = () => { try { return JSON.parse(localStorage.getItem(KEY)); } catch (e) { return null; } };
    const persist = (s) => localStorage.setItem(KEY, JSON.stringify(s));
    const clear = () => { localStorage.removeItem(KEY); localStorage.removeItem(PROFILE_KEY); };
    const getCachedProfile = () => { try { return JSON.parse(localStorage.getItem(PROFILE_KEY)); } catch (e) { return null; } };
    const cacheProfile = (p) => localStorage.setItem(PROFILE_KEY, JSON.stringify(p));

    async function getIdToken() {
      let s = getSession();
      if (!s) throw new ApiError('NO_SESSION', 'Sesión no iniciada.', 401);
      if (Date.now() >= s.expiresAt) {
        const d = await request(`${TOKEN_URL}?key=${CONFIG.apiKey}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'grant_type=refresh_token&refresh_token=' + encodeURIComponent(s.refreshToken),
        });
        s = toSession(d, s.username);
        persist(s);
      }
      return s.idToken;
    }

    async function dbFetch(path, options = {}, tokenOverride) {
      const token = tokenOverride || await getIdToken();
      return request(`${DB()}/${path}`, {
        ...options,
        headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + token, ...(options.headers || {}) },
      });
    }

    async function signUp(username, password) {
      const d = await authCall('signUp', { email: toEmail(username), password, returnSecureToken: true });
      const s = toSession(d, cleanUsername(username));
      persist(s);
      return s;
    }

    async function signIn(identifier, password, save = true) {
      const d = await authCall('signInWithPassword', { email: toEmail(identifier), password, returnSecureToken: true });
      const s = toSession(d, String(identifier).trim().toLowerCase().replace(/^@/, ''));
      if (save) persist(s);
      return s;
    }

    async function fetchProfile(uid) {
      try { return parseUserDoc(await dbFetch('users/' + uid)); }
      catch (e) { if (e.status === 404) return null; throw e; }
    }

    async function createProfile(uid, name, username) {
      return parseUserDoc(await dbFetch('users?documentId=' + encodeURIComponent(uid), {
        method: 'POST',
        body: JSON.stringify({ fields: {
          name: { stringValue: name }, username: { stringValue: username },
          approved: { booleanValue: false }, createdAt: { stringValue: new Date().toISOString() },
        } }),
      }));
    }

    async function ensureProfile(sess, name) {
      const p = await fetchProfile(sess.uid);
      return p || createProfile(sess.uid, name || sess.username, sess.username);
    }

    // Palabra secreta para recuperar la contraseña (solo se guarda su huella, en una colección que nadie puede leer)
    async function saveRecovery(uid, word) {
      const hash = await sha256(uid + ':' + norm(word));
      await dbFetch('recovery?documentId=' + encodeURIComponent(uid), {
        method: 'POST',
        body: JSON.stringify({ fields: { hash: { stringValue: hash }, createdAt: { stringValue: new Date().toISOString() } } }),
      });
    }

    // Elimina la cuenta: exige la contraseña (re-autentica) y borra perfil + acceso
    async function deleteOwnAccount(password) {
      const s = getSession();
      if (!s) throw new ApiError('NO_SESSION', 'Sesión no iniciada.', 401);
      const fresh = await signIn(s.username, password, false);   // si la contraseña es incorrecta, lanza error aquí
      await dbFetch('users/' + fresh.uid, { method: 'DELETE' }, fresh.idToken);
      try { await dbFetch('recovery/' + fresh.uid, { method: 'DELETE' }, fresh.idToken); } catch (e) { /* puede no existir */ }
      await authCall('delete', { idToken: fresh.idToken });
      const uid = fresh.uid;
      clear();
      return uid;
    }

    // Solo CEO
    async function listUsers() {
      const out = [];
      let pageToken = '';
      do {
        const d = await dbFetch('users?pageSize=300' + (pageToken ? '&pageToken=' + encodeURIComponent(pageToken) : ''));
        (d.documents || []).forEach((doc) => out.push(parseUserDoc(doc)));
        pageToken = d.nextPageToken || '';
      } while (pageToken);
      return out;
    }

    async function setAccess(uid, approved) {
      await dbFetch(`users/${uid}?currentDocument.exists=true&updateMask.fieldPaths=approved&updateMask.fieldPaths=reviewedAt`, {
        method: 'PATCH',
        body: JSON.stringify({ fields: { approved: { booleanValue: approved }, reviewedAt: { stringValue: new Date().toISOString() } } }),
      });
    }

    return { getSession, persist, clear, getCachedProfile, cacheProfile, signUp, signIn, fetchProfile, createProfile, ensureProfile, saveRecovery, deleteOwnAccount, listUsers, setAccess };
  }

  // ---------------------------------------------------------------
  //  Recuperar contraseña (lo valida el servidor con los datos de registro)
  // ---------------------------------------------------------------
  async function recoverPassword({ nombre, usuario, palabra, nuevaPassword }) {
    let res;
    try {
      res = await fetch(CONFIG.recoveryUrl, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre, usuario: cleanUsername(usuario), palabra, nuevaPassword }),
      });
    } catch (e) { throw new ApiError('NETWORK', 'No se pudo contactar el servidor de recuperación. ¿Está encendido (node server.js)?', 0); }
    let data = {};
    try { data = await res.json(); } catch (e) { data = {}; }
    if (!res.ok) throw new ApiError('SERVER', data.error || 'No se pudo cambiar la contraseña.', res.status);
    return data;
  }

  // ---------------------------------------------------------------
  //  Estética: usa el mismo tema (color + claro/oscuro) que eligió el usuario en la agenda
  // ---------------------------------------------------------------
  const THEMES = {
    pink: { name: 'Rosa Neón', accent: '#ff1493', light: '#ff66c4' },
    purple: { name: 'Púrpura', accent: '#a855f7', light: '#c084fc' },
    blue: { name: 'Azul', accent: '#3b82f6', light: '#60a5fa' },
    teal: { name: 'Turquesa', accent: '#14b8a6', light: '#2dd4bf' },
    gold: { name: 'Dorado', accent: '#f59e0b', light: '#fbbf24' },
    rosegold: { name: 'Oro Rosa', accent: '#fda4af', light: '#fecdd3' },
    red: { name: 'Rojo', accent: '#ef4444', light: '#f87171' },
    green: { name: 'Esmeralda', accent: '#10b981', light: '#34d399' },
    dark: { name: 'Gris', accent: '#71717a', light: '#a1a1aa' },
  };
  const hexToRgba = (hex, a) => {
    const h = hex.replace('#', '');
    const n = parseInt(h, 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${a})`;
  };
  const isLight = () => localStorage.getItem('kore_light_mode') === 'true';
  function applyTheme() {
    const key = localStorage.getItem('kore_theme') || 'pink';
    const th = THEMES[key] || (key === 'rainbow' ? { accent: '#00dfd8', light: '#00dfd8' } : THEMES.pink);
    const light = isLight();
    const r = document.documentElement.style;
    r.setProperty('--accent', th.accent);
    r.setProperty('--accent-light', th.light);
    r.setProperty('--border-color', light ? 'rgba(0, 0, 0, 0.12)' : hexToRgba(th.accent, 0.3));
    document.body.classList.toggle('light-mode', light);
    const icon = document.getElementById('mode-icon');
    const text = document.getElementById('mode-text');
    if (icon) icon.className = light ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    if (text) text.textContent = light ? 'Claro' : 'Oscuro';
  }
  function toggleMode() {
    localStorage.setItem('kore_light_mode', String(!isLight()));
    applyTheme();
  }
  function setThemeKey(key) {
    localStorage.setItem('kore_theme', key);
    applyTheme();
  }

  global.KORE = {
    CONFIG, THEMES, isConfigured, ApiError, friendlyError, cleanUsername, validUsername, norm, esc,
    account, recoverPassword, applyTheme, toggleMode, setThemeKey,
  };
})(window);
