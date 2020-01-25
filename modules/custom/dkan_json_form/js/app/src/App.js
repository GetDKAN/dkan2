import React, { useState, useEffect } from 'react';
import Form from "react-jsonschema-form";
const axios = require('axios');

function App() {

  const [schema, setSchema] = useState({})
  const [formData, setFormData] = useState({})


  useEffect(() => {
    async function fetchSchema() {
      const response = await axios.get('/api/1/metastore/schemas/dataset');
      setSchema(response.data);

      const id = getId()
      if (id) {
        const response2 = await axios.get('/api/1/metastore/schemas/dataset/items/' + id);
        setFormData(response2.data);
      }
    }  
  
    fetchSchema();
  }, []);

  function submitDataset(event) {
    const id = getId();
    if (id) {
      axios.put('/api/1/metastore/schemas/dataset/items/' + id, event.formData);
    }
    else {
      axios.post('/api/1/metastore/schemas/dataset/items', event.formData);
    }
  }

  function getId() {
    const urlParams = new URLSearchParams(window.location.search);
    const ids = urlParams.getAll('id');
    if (ids.length > 0) {
      return ids[0];
    }
    return null;
  }

  return (
    <Form schema={schema} formData={formData}
        onSubmit={ (e) => {submitDataset(e)} }
        onError={(e) => { console.log(e)}} />
  );
}

export default App;
