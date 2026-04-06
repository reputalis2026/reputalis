#!/usr/bin/env python3
"""
Procesa 5 imágenes de caritas para la encuesta Reputalis:

  - Elimina fondo casi blanco (tolerancia RGB configurable).
  - Recorta al contenido visible (alpha > umbral).
  - Escala sin deformar y centra en un canvas PNG cuadrado (p. ej. 512×512).
  - Guarda como public/survey-rating/faces/cara1.png … cara5.png

Requisitos:
  pip install Pillow

Repetir con nuevas fotos:
  python scripts/process_survey_faces.py fuentes/a.jpg fuentes/b.png ...

Las rutas deben ser exactamente 5, en orden del score 1 → 5.
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path


def _require_pillow():
    try:
        from PIL import Image, ImageFilter

        return Image, ImageFilter
    except ImportError:
        print("Instala Pillow: pip install -r scripts/requirements-faces.txt", file=sys.stderr)
        sys.exit(1)


def remove_near_white_rgba(img, tolerance: int):
    """RGB o RGBA → RGBA; píxeles cercanos a blanco pasan a transparentes."""
    rgba = img.convert("RGBA")
    pixels = rgba.load()
    w, h = rgba.size
    tol = max(0, min(255, tolerance))

    for y in range(h):
        for x in range(w):
            r, g, b, a = pixels[x, y]
            if a == 0:
                continue
            if r >= 255 - tol and g >= 255 - tol and b >= 255 - tol:
                pixels[x, y] = (r, g, b, 0)

    return rgba


def soften_alpha(rgba, radius: float, ImageFilter) -> object:
    """Suaviza el canal alpha del recorte (reduce bordes duros tras quitar blanco)."""
    if radius <= 0:
        return rgba
    a_band = rgba.split()[3]
    a_smooth = a_band.filter(ImageFilter.GaussianBlur(radius=min(radius, 2.0)))
    out = rgba.copy()
    out.putalpha(a_smooth)
    return out


def content_bbox(img_rgba, alpha_cutoff: int) -> tuple[int, int, int, int] | None:
    """Bbox (l, t, r, b) donde alpha > cutoff; None si vacío."""
    alpha = img_rgba.split()[3]
    # Máscara binaria para getbbox
    mask = alpha.point(lambda p: 255 if p > alpha_cutoff else 0)
    return mask.getbbox()


def fit_center_on_canvas(cropped, canvas_size: int, fill_ratio: float, resample: int, pil) -> object:
    """Escala cropped (RGBA) manteniendo proporción y lo centra en canvas transparente. `pil` = módulo PIL.Image."""
    cw = ch = canvas_size
    max_side = int(canvas_size * fill_ratio)
    if max_side < 1:
        max_side = canvas_size

    w, h = cropped.size
    if w < 1 or h < 1:
        raise ValueError("Imagen recortada sin tamaño válido")

    scale = min(max_side / w, max_side / h)
    nw = max(1, int(round(w * scale)))
    nh = max(1, int(round(h * scale)))
    resized = cropped.resize((nw, nh), resample)

    out = pil.new("RGBA", (cw, ch), (0, 0, 0, 0))
    x = (cw - nw) // 2
    y = (ch - nh) // 2
    out.paste(resized, (x, y), resized)
    return out


def process_one(
    src_path: Path,
    dst_path: Path,
    canvas_size: int,
    tolerance: int,
    fill_ratio: float,
    alpha_cutoff: int,
    pad_px: int,
    edge_soften: int,
) -> None:
    Image, ImageFilter = _require_pillow()
    img = Image.open(src_path)
    rgba = remove_near_white_rgba(img, tolerance=tolerance)
    bbox = content_bbox(rgba, alpha_cutoff=alpha_cutoff)
    if bbox is None:
        raise RuntimeError(f"Sin contenido visible tras quitar fondo: {src_path}")

    l, t, r, b = bbox
    l = max(0, l - pad_px)
    t = max(0, t - pad_px)
    r = min(rgba.width, r + pad_px)
    b = min(rgba.height, b + pad_px)
    cropped = rgba.crop((l, t, r, b))
    cropped = soften_alpha(cropped, float(edge_soften), ImageFilter)

    try:
        resample = Image.Resampling.LANCZOS  # Pillow >= 9.1
    except AttributeError:
        resample = Image.LANCZOS

    final_img = fit_center_on_canvas(
        cropped,
        canvas_size,
        fill_ratio,
        resample,
        Image,
    )
    dst_path.parent.mkdir(parents=True, exist_ok=True)
    final_img.save(dst_path, format="PNG", optimize=True)


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Genera cara1..cara5.png con fondo transparente y tamaño uniforme."
    )
    parser.add_argument(
        "inputs",
        nargs=5,
        metavar="ARCHIVO",
        help="Exactamente 5 imágenes (score 1 … 5 en ese orden)",
    )
    parser.add_argument(
        "--out-dir",
        type=Path,
        default=Path(__file__).resolve().parent.parent / "public" / "survey-rating" / "faces",
        help="Directorio de salida (default: public/survey-rating/faces)",
    )
    parser.add_argument("--size", type=int, default=512, help="Lado del PNG cuadrado (default 512)")
    parser.add_argument(
        "--tolerance",
        type=int,
        default=35,
        help="0–255: cuánto puede desviarse cada canal de 255 y seguir siendo 'blanco' (default 35)",
    )
    parser.add_argument(
        "--fill",
        type=float,
        default=0.92,
        help="Fracción del canvas que puede ocupar el lado mayor del contenido (default 0.92)",
    )
    parser.add_argument(
        "--alpha-cutoff",
        type=int,
        default=8,
        help="Recorte: ignorar píxeles con alpha <= este valor (default 8)",
    )
    parser.add_argument(
        "--pad",
        type=int,
        default=2,
        help="Píxeles de margen extra alrededor del bbox antes de escalar (default 2)",
    )
    parser.add_argument(
        "--edge-soften",
        type=int,
        default=1,
        help="Radio de suavizado en alpha tras quitar blanco (0 desactiva; default 1)",
    )

    args = parser.parse_args()

    for i, src in enumerate(args.inputs, start=1):
        p = Path(src).expanduser().resolve()
        if not p.is_file():
            print(f"No existe el archivo: {p}", file=sys.stderr)
            return 1

    out_dir = args.out_dir.resolve()
    for i, src in enumerate(args.inputs, start=1):
        dst = out_dir / f"cara{i}.png"
        print(f"  [{i}/5] {Path(src).name} → {dst}")
        try:
            process_one(
                Path(src).expanduser().resolve(),
                dst,
                canvas_size=args.size,
                tolerance=args.tolerance,
                fill_ratio=args.fill,
                alpha_cutoff=args.alpha_cutoff,
                pad_px=args.pad,
                edge_soften=args.edge_soften,
            )
        except Exception as e:
            print(f"Error procesando score {i}: {e}", file=sys.stderr)
            return 1

    print("Listo.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
