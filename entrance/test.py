import flet as ft

primaryColor = '#fffcf4'
darkerWhiteBg = '#e3e3e3'
secondaryColor = '#7F63F4'

HEADER_HEIGHT = 96  # z. B. 8% von 1200px

def generate_header() -> ft.Container:
    title = ft.Text(
        "Herbstball 2025 - Marie Curie meets Friedlieb Runge",
        size=32, weight=ft.FontWeight.BOLD, color="white"
    )
    subtitle = ft.Text("Einlass", size=16, color="white")

    return ft.Container(
        content=ft.Column(
            [title, subtitle],
            alignment=ft.MainAxisAlignment.CENTER,
            horizontal_alignment=ft.CrossAxisAlignment.START,
            spacing=0,
        ),
        bgcolor=secondaryColor,
        height=HEADER_HEIGHT,
        padding=ft.padding.only(left=10),
        alignment=ft.alignment.center_left,
    )

def generate_login() -> ft.Container:
    return ft.Container(
        width=400,
        height=400,
        bgcolor=primaryColor,
        border_radius=10,
        alignment=ft.alignment.center,
    )

def main(page: ft.Page):
    page.bgcolor = darkerWhiteBg
    page.title = 'Herbstball 2025 - Einlasssystem'
    page.scroll = 'adaptive'
    page.padding = 0
    page.window_width = 1600
    page.window_height = 1200

    header = generate_header()
    login = generate_login()

    # Der Bereich unterhalb des Headers mit absoluter Zentrierung
    content = ft.Container(
        expand=True,
        content=ft.Stack([
            ft.Container(
                content=login,
                alignment=ft.alignment.center,
                expand=True,
            )
        ])
    )

    # Gesamtes Layout
    page.add(
        ft.Column(
            controls=[header, content],
            expand=True
        )
    )

ft.app(main)