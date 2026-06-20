import React, { useState } from 'react';
import { laravelTemplates } from '../data/laravelTemplates';
import { BladeFile } from '../types';
import JSZip from 'jszip';

export default function BladeExplorer() {
  const [selectedFile, setSelectedFile] = useState<BladeFile>(laravelTemplates[0]);
  const [copied, setCopied] = useState<boolean>(false);
  const [isZipping, setIsZipping] = useState<boolean>(false);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(selectedFile.code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Error al copiar el código:', err);
    }
  };

  const downloadAllAsZip = async () => {
    try {
      setIsZipping(true);
      const zip = new JSZip();

      laravelTemplates.forEach((file) => {
        zip.file(file.path, file.code);
      });

      const content = await zip.generateAsync({ type: 'blob' });
      const url = URL.createObjectURL(content);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'laravel_ferreteria_export.zip';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch (err) {
      console.error('Error al generar el ZIP:', err);
      alert('Hubo un error al generar el archivo ZIP.');
    } finally {
      setIsZipping(false);
    }
  };

  return (
    <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
      <div className="card-header bg-dark text-white p-3 d-flex align-items-center justify-content-between">
        <div className="d-flex align-items-center gap-2">
          <i className="bi bi-folder-fill text-warning fs-4"></i>
          <div>
            <h5 className="mb-0 fw-bold">resources/views/</h5>
            <small className="text-secondary">Explora la estructura Blade completa para tu proyecto Laravel 10+</small>
          </div>
        </div>
        <span className="badge bg-warning text-dark font-monospace">LARAVEL MVC FRONTEND</span>
      </div>

      <div className="card-body p-0">
        <div className="row g-0" style={{ minHeight: '620px' }}>
          
          {/* Columna Izquierda: Árbol de Directorios y Archivos */}
          <div className="col-md-4 col-lg-3 border-end bg-light p-3">
            <h6 className="fw-bold text-muted small text-uppercase mb-3 px-2 tracking-wider">Estructura del Proyecto</h6>
            
            <div className="list-group list-group-flush" style={{ fontSize: '0.9rem' }}>
              
              {/* Directorios Principales */}
              <div className="mb-3 px-2">
                <div className="d-flex align-items-center gap-1 text-secondary mb-1">
                  <i className="bi bi-folder2-open text-warning"></i>
                  <span className="fw-semibold text-dark">resources</span>
                </div>
                
                <div className="ps-3 mb-1">
                  <div className="d-flex align-items-center gap-1 text-secondary mb-1">
                    <i className="bi bi-folder2-open text-warning"></i>
                    <span className="fw-semibold text-dark">views</span>
                  </div>

                  {/* Listado de Archivos Blade */}
                    <div className="ps-2 mt-2 d-grid gap-1">
                    {laravelTemplates.filter(f => f.path.startsWith('resources')).map((file) => {
                      const isActive = file.path === selectedFile.path;
                      // Formatear la vista del nombre de archivo quitando resources/views/
                      const displayName = file.path.replace('resources/views/', '');

                      return (
                        <button
                          type="button"
                          key={file.path}
                          onClick={() => {
                            setSelectedFile(file);
                            setCopied(false);
                          }}
                          className={`btn btn-sm text-start py-2 px-2.5 border-0 d-flex align-items-center gap-2 ${
                            isActive
                              ? 'bg-primary text-white shadow-sm fw-semibold'
                              : 'text-dark hover:bg-light'
                          }`}
                          style={{ borderRadius: '6px', fontSize: '0.85rem', transition: 'all 0.15s' }}
                        >
                          <i className={`bi ${file.icon} ${isActive ? 'text-white' : 'text-primary'}`}></i>
                          <span className="text-truncate">{displayName}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Carpeta Database */}
              <div className="mb-3 px-2">
                <div className="d-flex align-items-center gap-1 text-secondary mb-1">
                  <i className="bi bi-folder2-open text-warning"></i>
                  <span className="fw-semibold text-dark">database</span>
                </div>
                
                <div className="ps-3 mb-1">
                  <div className="d-flex align-items-center gap-1 text-secondary mb-1">
                    <i className="bi bi-folder2-open text-warning"></i>
                    <span className="fw-semibold text-dark">migrations</span>
                  </div>

                  {/* Listado de Archivos de Migraciones */}
                  <div className="ps-2 mt-2 d-grid gap-1">
                    {laravelTemplates.filter(f => f.path.startsWith('database')).map((file) => {
                      const isActive = file.path === selectedFile.path;
                      const displayName = file.path.replace('database/migrations/', '');
                      
                      return (
                        <button
                          key={file.path}
                          onClick={() => {
                            setSelectedFile(file);
                            setCopied(false);
                          }}
                          className={`btn btn-sm text-start py-2 px-2.5 border-0 d-flex align-items-center gap-2 ${
                            isActive
                              ? 'bg-primary text-white shadow-sm fw-semibold'
                              : 'text-dark hover:bg-light'
                          }`}
                          style={{ borderRadius: '6px', fontSize: '0.85rem', transition: 'all 0.15s' }}
                          id={`migration-file-${file.path.replaceAll('/', '-')}`}
                        >
                          <i className={`bi ${file.icon} ${isActive ? 'text-white' : 'text-primary'}`}></i>
                          <span className="text-truncate">{displayName}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Carpeta App */}
              <div className="mb-3 px-2">
                <div className="d-flex align-items-center gap-1 text-secondary mb-1">
                  <i className="bi bi-folder2-open text-warning"></i>
                  <span className="fw-semibold text-dark">app</span>
                </div>
                
                <div className="ps-3 mb-1">
                  <div className="d-flex align-items-center gap-1 text-secondary mb-1">
                    <i className="bi bi-folder2-open text-warning"></i>
                    <span className="fw-semibold text-dark">Http/Controllers</span>
                  </div>
                  <div className="ps-2 mt-2 d-grid gap-1">
                    {laravelTemplates.filter(f => f.path.startsWith('app/Http/Controllers')).map((file) => {
                      const isActive = file.path === selectedFile.path;
                      const displayName = file.path.replace('app/Http/Controllers/', '');
                      return (
                        <button key={file.path} onClick={() => { setSelectedFile(file); setCopied(false); }} className={`btn btn-sm text-start py-2 px-2.5 border-0 d-flex align-items-center gap-2 ${isActive ? 'bg-primary text-white shadow-sm fw-semibold' : 'text-dark hover:bg-light'}`} style={{ borderRadius: '6px', fontSize: '0.85rem' }}>
                          <i className={`bi ${file.icon} ${isActive ? 'text-white' : 'text-primary'}`}></i> <span className="text-truncate">{displayName}</span>
                        </button>
                      );
                    })}
                  </div>

                  <div className="d-flex align-items-center gap-1 text-secondary mb-1 mt-3">
                    <i className="bi bi-folder2-open text-warning"></i>
                    <span className="fw-semibold text-dark">Models</span>
                  </div>
                  <div className="ps-2 mt-2 d-grid gap-1">
                    {laravelTemplates.filter(f => f.path.startsWith('app/Models')).map((file) => {
                      const isActive = file.path === selectedFile.path;
                      const displayName = file.path.replace('app/Models/', '');
                      return (
                        <button key={file.path} onClick={() => { setSelectedFile(file); setCopied(false); }} className={`btn btn-sm text-start py-2 px-2.5 border-0 d-flex align-items-center gap-2 ${isActive ? 'bg-primary text-white shadow-sm fw-semibold' : 'text-dark hover:bg-light'}`} style={{ borderRadius: '6px', fontSize: '0.85rem' }}>
                          <i className={`bi ${file.icon} ${isActive ? 'text-white' : 'text-primary'}`}></i> <span className="text-truncate">{displayName}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Carpeta Routes */}
              <div className="mb-3 px-2">
                <div className="d-flex align-items-center gap-1 text-secondary mb-1">
                  <i className="bi bi-folder2-open text-warning"></i>
                  <span className="fw-semibold text-dark">routes</span>
                </div>
                <div className="ps-3 mb-1">
                  <div className="ps-2 mt-2 d-grid gap-1">
                    {laravelTemplates.filter(f => f.path.startsWith('routes')).map((file) => {
                      const isActive = file.path === selectedFile.path;
                      const displayName = file.path.replace('routes/', '');
                      return (
                        <button key={file.path} onClick={() => { setSelectedFile(file); setCopied(false); }} className={`btn btn-sm text-start py-2 px-2.5 border-0 d-flex align-items-center gap-2 ${isActive ? 'bg-primary text-white shadow-sm fw-semibold' : 'text-dark hover:bg-light'}`} style={{ borderRadius: '6px', fontSize: '0.85rem' }}>
                          <i className={`bi ${file.icon} ${isActive ? 'text-white' : 'text-primary'}`}></i> <span className="text-truncate">{displayName}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Carpeta pública */}
              <div className="mb-3 px-2 opacity-75">
                <div className="d-flex align-items-center gap-1 text-muted">
                  <i className="bi bi-folder-fill text-muted"></i>
                  <span>public/</span>
                </div>
                <div className="ps-3">
                  <small className="text-muted text-xs d-block">assets/, css/, js/ (Bootstrap)</small>
                </div>
              </div>
            </div>

            <div className="bg-white border rounded p-3 mt-4" style={{ fontSize: '0.82rem' }}>
              <h6 className="fw-bold text-dark mb-2"><i className="bi bi-info-circle-fill text-primary"></i> ¿Cómo usar este código?</h6>
              <p className="text-muted mb-2">
                Este codigo esta completamente estructurado con clases de Bootstrap 5 e iconos. Todo está optimizado para la arquitectura MVC de Laravel.
              </p>
              <ol className="mb-0 ps-3 text-muted">
                <li>Crea el archivo en tu workspace local siguiendo la ruta marcada arriba.</li>
                <li>Pega el codigo correspondiente.</li>
                <li>Llama a los controladores correspondientes para retornar las vistas.</li>
              </ol>
            </div>
          </div>

          {/* Columna Derecha: Detalle de la Vista Seleccionada y Código Fuente */}
          <div className="col-md-8 col-lg-9 d-flex flex-column bg-white">
            
            {/* Cabecera del Archivo Activo */}
            <div className="p-3 border-bottom bg-light d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <span className="badge bg-secondary-subtle text-dark border font-mono px-2 py-1 mb-1">
                  Ruta: {selectedFile.path}
                </span>
                <h5 className="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                  <i className={`bi ${selectedFile.icon} text-primary`}></i>
                  {selectedFile.title}
                </h5>
                <p className="text-muted small mb-0 mt-1">{selectedFile.description}</p>
              </div>

              <div className="d-flex gap-2">
                <button
                  onClick={downloadAllAsZip}
                  className="btn btn-outline-secondary fw-bold px-3 d-flex align-items-center gap-2"
                  style={{ borderRadius: '8px' }}
                  disabled={isZipping}
                >
                  <i className={`bi ${isZipping ? 'bi-hourglass-split' : 'bi-file-earmark-zip'}`}></i>
                  <span>{isZipping ? 'Comprimiendo...' : 'Descargar ZIP'}</span>
                </button>
                <button
                  onClick={handleCopy}
                  className={`btn ${copied ? 'btn-success' : 'btn-primary'} fw-bold px-3 d-flex align-items-center gap-2`}
                  style={{ borderRadius: '8px' }}
                  id="btn-copy-code"
                >
                  <i className={`bi ${copied ? 'bi-check-lg' : 'bi-clipboard-fill'}`}></i>
                  <span>{copied ? '¡Copiado!' : 'Copiar Código'}</span>
                </button>
              </div>
            </div>

            {/* Editor de Código (Solo lectura con formato mono) */}
            <div className="grow p-0 m-0 bg-dark position-relative" style={{ maxHeight: '680px', overflowY: 'auto' }}>
              <pre className="p-4 m-0 text-light font-mono" style={{ fontSize: '0.82rem', lineHeight: '1.5' }}>
                <code>{selectedFile.code}</code>
              </pre>
            </div>

            {/* Indicaciones finales */}
            <div className="p-3 border-top bg-light text-muted" style={{ fontSize: '0.8rem' }}>
              <div className="d-flex justify-content-between align-items-center">
                <span>Directiva en Laravel: <code>blade</code> motor de plantillas integrado.</span>
                <span className="text-secondary">Usa <code>{"@{{ $variable }}"}</code> para imprimir datos desde tu base de datos o controlador.</span>
              </div>
            </div>

          </div>

        </div>
      </div>
    </div>
  );
}
