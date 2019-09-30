context('SQL Endpoint', () => {

    let user_credentials = Cypress.env("TEST_USER_CREDENTIALS");
    let resource_identifier

    // Obtain the resource identifier then import its data.
    before(function() {
        cy.fixture('electionDistricts').then((json) => {
            cy.request('metastore/schemas/dataset/items/' + json.uuid + '?show-reference-ids').then((response) => {
                expect(response.status).eql(200);
                resource_identifier = response.body.distribution[0].identifier;
                expect(resource_identifier).not.eql(json.uuid)
                expect(resource_identifier).to.match(new RegExp(Cypress.env('UUID_REGEX')));
                cy.request({
                    method: 'POST',
                    url: 'datastore/imports/',
                    body: {
                        "resource_id": resource_identifier
                    },
                    auth: user_credentials
                }).then((response) => {
                    expect(response.status).eql(200);
                })
            })
        })
    })

    after(function() {
      cy.request(
        {
          method: 'DELETE',
          url: 'datastore/imports/' + resource_identifier,
          auth: user_credentials
        }
      ).then((response) => {
        expect(response.status).eql(200);
      })
    })

    context('SELECT', () => {
        it('All', () => {
            let query = `[SELECT * FROM ${resource_identifier}];`
            cy.request('datastore/sql?query=' + query).then((response) => {
                expect(response.status).eql(200)
                expect(response.body.length).eql(399)
                cy.fixture('electionDistricts').then((json) => {
                    json.properties.forEach((x) => {
                        expect(response.body[0].hasOwnProperty(x)).equal(true)
                    })
                })
            })
        })

        it('Specific fields', () => {
            let query = `[SELECT lon,lat FROM ${resource_identifier}];`
            cy.request('datastore/sql?query=' + query).then((response) => {
                expect(response.status).eql(200)
                expect(response.body.length).eql(399)
                let properties = [
                    "lat",
                    "lon"
                ]

                properties.forEach((x) => {
                    expect(response.body[0].hasOwnProperty(x)).equal(true)
                })
                expect(response.body[0].hasOwnProperty("prov_id")).equal(false)
            })
        })
    })

    context('WHERE', () => {
        it('Single condition', () => {
            let query = `[SELECT * FROM ${resource_identifier}][WHERE prov_name = 'Farah'];`
            cy.request('datastore/sql?query=' + query).then((response) => {
                expect(response.status).eql(200)
                expect(response.body.length).eql(11)
            })
        })

        it('Multiple conditions', () => {
            let query = `[SELECT * FROM ${resource_identifier}][WHERE prov_name = 'Farah' AND dist_name = 'Farah'];`
            cy.request('datastore/sql?query=' + query).then((response) => {
                expect(response.status).eql(200)
                expect(response.body.length).eql(1)
            })
        })

    })

    context('ORDER BY', () => {

        it('Ascending explicit', () => {
            let query = `[SELECT * FROM ${resource_identifier}][ORDER BY prov_name ASC];`
            cy.request('datastore/sql?query=' + query).then((response) => {
                expect(response.status).eql(200)
                expect(response.body.length).eql(399)
                expect(response.body[0].prov_name).eql("Badakhshan")
            })
        })

        it('Descending explicit', () => {
            let query = `[SELECT * FROM ${resource_identifier}][ORDER BY prov_name DESC];`
            cy.request('datastore/sql?query=' + query).then((response) => {
                expect(response.status).eql(200)
                expect(response.body.length).eql(399)
                expect(response.body[0].prov_name).eql("Zabul")
            })
        })

    })

    context('LIMIT and OFFSET', () => {
        it('Limit only', () => {
            let query = `[SELECT * FROM ${resource_identifier}][ORDER BY prov_name ASC][LIMIT 5];`
            cy.request('datastore/sql?query=' + query).then((response) => {
                expect(response.status).eql(200)
                expect(response.body.length).eql(5)
                expect(response.body[0].prov_name).eql("Badakhshan")
            })
        })

        it('Limit and offset', () => {
            let query = `[SELECT * FROM ${resource_identifier}][ORDER BY prov_name ASC][LIMIT 5 OFFSET 100];`
            cy.request('datastore/sql?query=' + query).then((response) => {
                expect(response.status).eql(200)
                expect(response.body.length).eql(5)
                expect(response.body[0].prov_name).eql("Faryab")
            })
        })

    })

})
