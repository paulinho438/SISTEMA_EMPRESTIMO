export default class ExternoService {

/**
   * Descrição: Traz dados do cep
   * metodo: GET
   */
  async getEndereco(cep) {     
    let req = null;
    try {
      req = await fetch("https://viacep.com.br/ws/"+cep+"/json/");
    } catch (err) {
      return err;
    }
    return await req.json();
  }
  
  /**
   * Descrição: Traz dados do cep (latitude e longitude)
   * metodo: GET
   */
  async getLatLong(cep) {     
    let req = null;
    try {
      req = await fetch("https://maps.googleapis.com/maps/api/geocode/json?address="+cep+"&key="+import.meta.env.VITE_APP_GOOGLE_MAPS_KEY, {
        method: 'GET',
        dataType: 'jsonp',
      });
    } catch (err) {
      return err;
    }
    return await req.json();
  }

}
