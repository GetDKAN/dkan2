import React, { useState, useEffect } from 'react';
import Form from "react-jsonschema-form";
import 'bootstrap-lite/lib.bootstrap.css';
const axios = require('axios');

function App() {
  const baseUrl = "";

  const [schema, setSchema] = useState({})
  const [uiSchema, setUiSchema] = useState({})
  const [formData, setFormData] = useState({})

  useEffect(() => {

    async function fetchSchema() {
      const response = await axios.get(baseUrl + '/api/1/metastore/schemas/dataset');
      setSchema(response.data);

      const response2 = await axios.get(baseUrl + '/api/1/metastore/schemas/dataset.ui');
      setUiSchema(response2.data);

      const id = getId()
      if (id) {
        const response3 = await axios.get(baseUrl + '/api/1/metastore/schemas/dataset/items/' + id);
        setFormData(response3.data);
      }
    }  
  
    fetchSchema();
  }, []);

  function submitDataset(event) {
    const id = getId();
    if (id) {
      axios.put(baseUrl + '/api/1/metastore/schemas/dataset/items/' + id, event.formData);
    }
    else {
      axios.post(baseUrl + '/api/1/metastore/schemas/dataset/items', event.formData);
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
    <Form schema={schema} formData={formData} uiSchema={uiSchema}
        onSubmit={ (e) => {submitDataset(e)} }
        onError={(e) => { console.log(e)}} />
  );
}

export default App;
