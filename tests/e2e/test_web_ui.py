"""
Pruebas end-to-end de la interfaz web (Cocina y Administración) con Selenium.

Cada prueba guarda capturas numeradas en reports/e2e/screenshots/ como evidencia.
"""

import re
import time

import requests
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select

from conftest import BASE_URL, USERS


def _int(text):
    m = re.search(r"\d+", text or "")
    return int(m.group()) if m else None


def _items(payload, key):
    """La API devuelve listas planas o envueltas en una clave."""
    if isinstance(payload, list):
        return payload
    return payload.get(key) or payload.get("data") or []


# ---------------------------------------------------------------------------
# 1. Login con credenciales inválidas
# ---------------------------------------------------------------------------
def test_01_login_con_credenciales_invalidas_muestra_error(ui):
    ui.open()
    ui.shot("pantalla de login")

    ui.login("intruso@restaurante.com", "contrasena-incorrecta")
    ui.shot("credenciales incorrectas escritas")
    ui.submit_login()

    error = ui.visible(By.ID, "login-error")
    assert error.text.strip(), "Debe mostrarse un mensaje de error"
    assert ui.d.find_element(By.ID, "view-login").is_displayed()
    assert not ui.d.find_element(By.ID, "view-cocina").is_displayed()
    assert not ui.d.find_element(By.ID, "view-admin").is_displayed()
    assert ui.d.execute_script("return localStorage.getItem('token');") is None
    ui.shot("mensaje de error y sin sesion")


# ---------------------------------------------------------------------------
# 2. Login con rol Cocina
# ---------------------------------------------------------------------------
def test_02_login_cocina_muestra_modulo_de_cocina(ui):
    ui.login_as("cocina")

    ui.visible(By.ID, "view-cocina")
    usuario = ui.visible(By.ID, "user-display-cocina")
    ui.wait.until(lambda d: "(Cocina)" in usuario.text)
    assert not ui.d.find_element(By.ID, "view-admin").is_displayed()
    # Cocina no ve el acceso a Administración
    assert not ui.d.find_element(By.ID, "btn-return-admin").is_displayed()
    ui.wait.until(lambda d: _int(d.find_element(By.ID, "badge-count-todas").text) is not None)
    ui.shot("modulo de cocina con comandas")


# ---------------------------------------------------------------------------
# 3. Login con rol Administrador
# ---------------------------------------------------------------------------
def test_03_login_admin_muestra_panel_con_metricas(ui):
    ui.login_as("admin")

    ui.visible(By.ID, "view-admin")
    usuario = ui.visible(By.ID, "user-display-admin")
    ui.wait.until(lambda d: "(Administrador)" in usuario.text)
    # Las métricas se llenan con datos reales de la API
    ui.wait.until(lambda d: (_int(d.find_element(By.ID, "metric-platillos").text) or 0) > 0)
    # La métrica de mesas es "ocupadas/total" (p. ej. "0/10"): se valida el total.
    ui.wait.until(lambda d: re.search(r"/\s*([1-9]\d*)", d.find_element(By.ID, "metric-mesas").text))
    assert not ui.d.find_element(By.ID, "view-cocina").is_displayed()
    ui.shot("panel de administracion con metricas")


# ---------------------------------------------------------------------------
# 4. Orden enviada desde la app móvil aparece en Cocina y se prepara
# ---------------------------------------------------------------------------
def test_04_orden_de_app_movil_aparece_en_cocina_y_se_prepara(ui):
    # 4.1 El mesero envía una comanda por la API (lo mismo que hace la app móvil)
    api = requests.Session()
    api.headers.update({"Accept": "application/json"})
    r = api.post(f"{BASE_URL}/api/login", json=dict(zip(("email", "password"), USERS["mesero"])), timeout=120)
    r.raise_for_status()
    api.headers["Authorization"] = f"Bearer {r.json()['token']}"

    mesa = _items(api.get(f"{BASE_URL}/api/mesas", timeout=60).json(), "mesas")[0]
    platillos = _items(api.get(f"{BASE_URL}/api/platillos", timeout=60).json(), "platillos")
    platillo = next(p for p in platillos if p.get("disponible"))
    r = api.post(f"{BASE_URL}/api/ordenes", timeout=60, json={
        "mesa_id": mesa["id"],
        "items": [{"platillo_id": platillo["id"], "cantidad": 2, "notas": "Enviada por prueba Selenium (app móvil)"}],
    })
    assert r.status_code == 201, r.text
    orden_id = r.json()["orden"]["id"]

    # 4.2 La cocina la ve aparecer en su pantalla
    ui.login_as("cocina")
    ui.visible(By.ID, "view-cocina")
    preparar = f"//button[contains(@onclick, \"updateEstado({orden_id}, 'preparando')\")]"
    listo = f"//button[contains(@onclick, \"updateEstado({orden_id}, 'listo')\")]"
    boton = ui.clickable(By.XPATH, preparar)
    ui.d.execute_script("arguments[0].scrollIntoView({block: 'center'});", boton)
    ui.shot(f"orden {orden_id} pendiente recibida desde movil")
    listas_antes = _int(ui.d.find_element(By.ID, "badge-count-listo").text)

    # 4.3 Iniciar preparación
    boton.click()
    boton = ui.clickable(By.XPATH, listo)
    ui.d.execute_script("arguments[0].scrollIntoView({block: 'center'});", boton)
    ui.shot(f"orden {orden_id} en preparacion")

    # 4.4 Marcar como lista
    boton.click()
    ui.wait.until(EC.invisibility_of_element_located((By.XPATH, listo)))
    ui.wait.until(lambda d: _int(d.find_element(By.ID, "badge-count-listo").text) == listas_antes + 1)
    # Volver arriba: la cabecera fija taparía los filtros si la página quedó desplazada.
    ui.d.execute_script("window.scrollTo(0, 0);")
    ui.clickable(By.CSS_SELECTOR, "button[data-filter='listo']").click()
    ui.shot(f"orden {orden_id} lista para servir")


# ---------------------------------------------------------------------------
# 5. Filtros de cocina por estado
# ---------------------------------------------------------------------------
def test_05_filtros_de_cocina_muestran_solo_el_estado_elegido(ui):
    ui.login_as("cocina")
    ui.visible(By.ID, "view-cocina")
    ui.wait.until(lambda d: _int(d.find_element(By.ID, "badge-count-todas").text) is not None)

    for estado in ("pendiente", "preparando", "listo", "todas"):
        ui.clickable(By.CSS_SELECTOR, f"button[data-filter='{estado}']").click()
        time.sleep(0.4)  # el filtro re-renderiza la grilla en el navegador
        esperado = _int(ui.d.find_element(By.ID, f"badge-count-{estado}").text)
        tarjetas = ui.d.find_elements(By.CSS_SELECTOR, "#ordenes-grid > div")
        assert len(tarjetas) == esperado, f"Filtro {estado}: {len(tarjetas)} tarjetas, badge {esperado}"
        if estado != "todas":
            for card in tarjetas:
                assert f"card-{estado}" in card.get_attribute("class"), f"Tarjeta fuera del filtro {estado}"
        if esperado == 0:
            assert ui.d.find_element(By.ID, "ordenes-empty").is_displayed()
        ui.shot(f"filtro {estado} con {esperado} comandas")


# ---------------------------------------------------------------------------
# 6. Administrador crea un platillo
# ---------------------------------------------------------------------------
def test_06_admin_crea_un_platillo_nuevo(ui):
    nombre = f"Platillo Selenium {int(time.time())}"
    ui.login_as("admin")
    ui.visible(By.ID, "view-admin")

    ui.clickable(By.ID, "tab-btn-platillos").click()
    ui.visible(By.ID, "tab-content-platillos")
    ui.wait.until(lambda d: len(d.find_elements(By.CSS_SELECTOR, "#tabla-platillos tr")) > 0)
    ui.shot("catalogo de platillos")

    ui.clickable(By.XPATH, "//button[contains(normalize-space(), 'Nuevo Platillo')]").click()
    ui.visible(By.ID, "modal-platillo")
    ui.d.find_element(By.ID, "platillo-nombre").send_keys(nombre)
    categoria = Select(ui.d.find_element(By.ID, "platillo-categoria"))
    ui.wait.until(lambda d: len([o for o in categoria.options if o.get_attribute("value")]) > 0)
    categoria.select_by_index(next(i for i, o in enumerate(categoria.options) if o.get_attribute("value")))
    ui.d.find_element(By.ID, "platillo-precio").send_keys("149.50")
    ui.d.find_element(By.ID, "platillo-descripcion").send_keys("Creado automáticamente por la prueba E2E de Selenium")
    disponible = ui.d.find_element(By.ID, "platillo-disponible")
    if not disponible.is_selected():
        disponible.click()
    ui.shot("formulario de nuevo platillo lleno")

    ui.clickable(By.XPATH, "//button[normalize-space()='Guardar Platillo']").click()
    ui.wait.until(EC.invisibility_of_element_located((By.ID, "modal-platillo")))
    fila = ui.visible(By.XPATH, f"//tbody[@id='tabla-platillos']//tr[contains(., '{nombre}')]")
    ui.d.execute_script("arguments[0].scrollIntoView({block: 'center'});", fila)
    assert "149.5" in fila.text
    ui.shot("platillo creado en el catalogo")


# ---------------------------------------------------------------------------
# 7. Cerrar sesión
# ---------------------------------------------------------------------------
def test_07_cerrar_sesion_vuelve_al_login_y_borra_la_sesion(ui):
    ui.login_as("cocina")
    ui.visible(By.ID, "view-cocina")
    ui.shot("sesion iniciada en cocina")

    ui.clickable(By.XPATH, "//div[@id='view-cocina']//button[normalize-space()='Salir']").click()
    ui.visible(By.ID, "view-login")
    assert ui.d.execute_script("return localStorage.getItem('token');") is None

    # Al recargar no se recupera la sesión
    ui.d.refresh()
    ui.visible(By.ID, "view-login")
    assert not ui.d.find_element(By.ID, "view-cocina").is_displayed()
    ui.shot("login tras cerrar sesion y recargar")
