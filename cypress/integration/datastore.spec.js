context('Datastore API', () => {
  let expected_columns;
  let resource_identifier;
  let user_credentials = Cypress.env('TEST_USER_CREDENTIALS');

  before(() => {
    cy.fixture('electionDistricts').then((json) => {
      cy.request('metastore/schemas/dataset/items/' + json.uuid + '?show-reference-ids').then((response) => {
        expect(response.status).eql(200);
        resource_identifier = response.body.distribution[0].identifier;
        expect(resource_identifier).to.match(new RegExp(Cypress.env('UUID_REGEX')));
      });
      expected_columns = json.properties
    })
  });

  it('List imports', () => {
    cy.request({
      url: 'datastore/imports',
      auth: user_credentials
    }).then((response) => {
      let firstKey = Object.keys(response.body)[0];
      expect(response.status).eql(200);
      expect(response.body[firstKey].hasOwnProperty('fileFetcher')).equals(true);
      expect(response.body[firstKey].hasOwnProperty('fileFetcherStatus')).equals(true);
      expect(response.body[firstKey].hasOwnProperty('fileName')).equals(true);
    })
  });

  it('Import, Get Info, and Delete', () => {
    cy.request({
      method: 'POST',
      url: 'datastore/imports',
      auth: user_credentials,
      body: {
        "resource_id": resource_identifier
      }
    }).then((response) => {
      expect(response.status).eql(200);
      expect(response.body.FileFetcherResult.status).eql("done");
      expect(response.body.ImporterResult.status).eql("done");
    });

    cy.request('datastore/imports/' + resource_identifier).then((response) => {
      expect(response.status).eql(200);
      expect(response.body.columns).eql(expected_columns);
      expect(response.body.numOfRows).eql(399);
      expect(response.body.numOfColumns).eql(9);
    });

    cy.request({
      method: 'DELETE',
      url: 'datastore/imports/' + resource_identifier,
      auth: user_credentials
    }).then((response) => {
      expect(response.status).eql(200);
    });
  });

  it('GET empty', () => {
    cy.request({
      url: 'datastore/imports/' + resource_identifier,
      failOnStatusCode: false
    }).then((response) => {
      expect(response.body.message).eql("A datastore for resource " + resource_identifier + " does not exist.")
    })
  });

  it('GET openapi api spec', () => {
    cy.request('datastore').then((response) => {
      expect(response.status).eql(200);
      expect(response.body.hasOwnProperty('openapi')).equals(true);
    })
  });

});
