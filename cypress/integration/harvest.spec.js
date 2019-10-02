context('Harvest', () => {

  let user_credentials = Cypress.env("TEST_USER_CREDENTIALS");

  // Set up.
  before(() => {

  });

  // Clean up.
  after(() => {

  });

  context('GET api/1/harvest/plans', () => {
    it('List harvest identifiers', () => {
      cy.request({
        url: 'harvest/plans',
        auth: user_credentials
      }).then((response) => {
        expect(response.status).eql(200);
      })
    })

    it('Requires authenticated user', () => {
      cy.request({
        url: 'harvest/plans',
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).eql(401)
      })
    });
  });

  context('POST api/1/harvest/plans', () => {
    it.skip('Register a new harvest', () => {

    });

    it('Requires authenticated user', () => {
      cy.request({
        url: 'harvest/plans',
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).eql(401)
      })
    })
  });

  context('GET api/1/harvest/plans/PLAN_ID', () => {
    it.skip('Get a single harvest plan'), () => {

    };

    it('Requires authenticated user', () => {
      cy.request({
        url: 'harvest/runs',
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).eql(401)
      })
    })
  });

  context('GET apu/1/harvest/runs?plan=PLAN_ID', () => {
    it.skip('Gives list of previous runs for a harvest id', () => {

    });

    it('Requires authenticated user', () => {
      cy.request({
        url: 'harvest/runs?plan=PLAN_ID',
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).eql(401)
      })
    })
  });

  context('POST api/1/harvest/runs', () => {
    it.skip('Run a harvest', () => {

    });

    it('Requires authenticated user', () => {
      cy.request({
        url: 'harvest/runs',
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).eql(401)
      })
    })
  });

  context('GET api/1/harvest/runs/{identifier}', () => {
    it.skip('Gives information about a single previous harvest run', () => {

    });

    it('Requires authenticated user', () => {
      cy.request({
        url: 'harvest/runs',
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).eql(401)
      })
    })
  });

});
