/**
 * React Refresh polyfill para desarrollo
 * 
 * Este archivo proporciona un objeto window.$RefreshReg$ y window.$RefreshSig$ vacíos
 * para evitar errores cuando se construye para producción.
 * 
 * En desarrollo, estos objetos son proporcionados por React Fast Refresh.
 */

if (import.meta.env.PROD) {
  window.$RefreshReg$ = () => {};
  window.$RefreshSig$ = () => (type) => type;
}