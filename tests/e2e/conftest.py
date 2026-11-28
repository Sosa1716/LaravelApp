"""
Configuración de las pruebas end-to-end con Selenium.

Variables de entorno:
  E2E_BASE_URL   URL de la aplicación a probar (por defecto http://localhost:8080).
                 En el pipeline es la URL de staging.
  E2E_HEADLESS   "0" para ver el navegador; por defecto corre sin ventana.

Las credenciales son las de los usuarios de demostración que crean los seeders
(solo existen en local y staging, nunca en producción).
"""

import os
import re
from pathlib import Path

import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

BASE_URL = os.environ.get("E2E_BASE_URL", "http://localhost:8080").rstrip("/")
HEADLESS = os.environ.get("E2E_HEADLESS", "1") != "0"
TIMEOUT = int(os.environ.get("E2E_TIMEOUT", "30"))

USERS = {
    "admin": ("admin@restaurante.com", "12345678"),
    "cocina": ("cocina@restaurante.com", "12345678"),
    "mesero": ("mesero@restaurante.com", "12345678"),
}

REPORT_DIR = Path(__file__).resolve().parents[2] / "reports" / "e2e"
SHOTS_DIR = REPORT_DIR / "screenshots"
SHOTS_DIR.mkdir(parents=True, exist_ok=True)


@pytest.fixture(scope="session")
def base_url():
    return BASE_URL


@pytest.fixture
def driver():
    opts = webdriver.ChromeOptions()
    if HEADLESS:
        opts.add_argument("--headless=new")
    opts.add_argument("--window-size=1366,900")
    opts.add_argument("--no-sandbox")
    opts.add_argument("--disable-dev-shm-usage")
    opts.add_argument("--lang=es-MX")
    # El gestor de contraseñas de Chrome detecta "12345678" (contraseña de los
    # usuarios demo) como filtrada y abre un aviso que quita el foco a la página:
    # a partir de ahí ningún clic llega al documento. Se desactiva en las pruebas.
    opts.add_experimental_option("prefs", {
        "credentials_enable_service": False,
        "profile.password_manager_enabled": False,
        "profile.password_manager_leak_detection": False,
    })
    opts.add_argument("--disable-features=PasswordLeakDetection,PasswordManagerOnboarding")
    drv = webdriver.Chrome(options=opts)  # Selenium Manager descarga el driver
    drv.set_page_load_timeout(120)  # el plan free de Render puede tardar al despertar
    yield drv
    drv.quit()


class Ui:
    """Acciones comunes sobre la interfaz y capturas numeradas por prueba."""

    def __init__(self, driver, test_name):
        self.d = driver
        self.wait = WebDriverWait(driver, TIMEOUT)
        self.test = test_name
        self.n = 0

    def shot(self, description):
        self.n += 1
        slug = re.sub(r"[^a-z0-9]+", "-", description.lower()).strip("-")
        path = SHOTS_DIR / f"{self.test}__{self.n:02d}-{slug}.png"
        self.d.save_screenshot(str(path))
        return path

    def open(self):
        self.d.get(BASE_URL + "/")
        self.d.execute_script("localStorage.clear();")
        self.d.get(BASE_URL + "/")
        return self.visible(By.ID, "view-login")

    def visible(self, by, value):
        return self.wait.until(EC.visibility_of_element_located((by, value)))

    def clickable(self, by, value):
        return self.wait.until(EC.element_to_be_clickable((by, value)))

    def login(self, email, password):
        self.visible(By.ID, "login-email").clear()
        self.d.find_element(By.ID, "login-email").send_keys(email)
        self.d.find_element(By.ID, "login-password").clear()
        self.d.find_element(By.ID, "login-password").send_keys(password)

    def submit_login(self):
        self.clickable(By.ID, "btn-login-submit").click()

    def login_as(self, role):
        self.open()
        self.login(*USERS[role])
        self.submit_login()


@pytest.fixture
def ui(driver, request):
    return Ui(driver, request.node.name)


@pytest.hookimpl(hookwrapper=True)
def pytest_runtest_makereport(item, call):
    """Si una prueba falla, guarda una captura del estado del navegador."""
    outcome = yield
    rep = outcome.get_result()
    if rep.when == "call" and rep.failed:
        drv = item.funcargs.get("driver")
        if drv is not None:
            drv.save_screenshot(str(SHOTS_DIR / f"{item.name}__FALLO.png"))
