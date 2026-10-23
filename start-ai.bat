@echo off
title SiMover AI Worker (Jl. Gubernur Soebardjo)
echo ============================================================
echo   MENYALAKAN AI WORKER SIMOVER
echo   Kamera Deteksi : Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal)
echo   Target API     : http://127.0.0.1:8080/api/ai
echo ============================================================
echo.
python -m ai_worker.worker --camera soebardjo
pause
