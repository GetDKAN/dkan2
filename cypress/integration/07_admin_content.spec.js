context('Admin content view', () => {

    beforeEach(() => {
        cy.drupalLogin('admin', 'admin')
        cy.visit("http://dkan/admin/content/node")
    })
    
    it('The admin content screen has an exposed data type filter that contains the values I expect.', () => {
        cy.get('h1').should('have.text', 'Content')
        cy.get('#edit-data-type').select('dataset',{ force: true }).should('have.value', 'dataset')
        cy.get('#edit-data-type').select('distribution',{ force: true }).should('have.value', 'distribution')
        cy.get('#edit-data-type').select('keyword',{ force: true }).should('have.value', 'keyword')
        cy.get('#edit-data-type').select('publisher',{ force: true }).should('have.value', 'publisher')
        cy.get('#edit-data-type').select('theme',{ force: true }).should('have.value', 'theme')
    })

    it('The content table has a column for Data Type', () => {
        cy.get('.vbo-table > thead > tr > #view-field-data-type-table-column > a').should('contain','Data Type');
    })

    it('The dataset data node titles should link to the REACT dataset page', () => {
        cy.get('#edit-data-type').select('dataset',{ force:true })
        cy.get('#edit-submit-dkan-content').click({ force:true })
        cy.get('tbody > :nth-child(1) > .views-field-title > a').invoke('attr', 'href').should('contain', '/dataset/');
    })

    it('There is a link to the datasets admin screen.', () => {
        cy.get('.toolbar-icon-system-admin-content').trigger('mouseover')
        cy.get('ul.toolbar-menu ul.toolbar-menu > .menu-item > a').invoke('attr', 'href').then(href => {
            cy.visit(href);
          });
        cy.get('h1').should('have.text', 'Datasets')
    })

})