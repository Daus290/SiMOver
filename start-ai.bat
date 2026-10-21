@echo off
title SiMover Multi-Camera AI Worker
echo ============================================================
echo   MENYALAKAN AI WORKER MULTI-KAMERA SIMOVER
echo   Kamera 1: Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal)
echo   Kamera 2: Simpang Sultan Adam - Sungai Andai
echo   Target API: http://localhost:8080/api/ai
echo ============================================================
echo.
python -m ai_worker.worker
pause
