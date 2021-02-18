
# Readme
-------
Welcome to the Conduction API Documentation parser!

This component, based on the [Commonground Proto Component](https://github.com/ConductionNL/Proto-Component-Commonground),
is aimed at checking APIs developed for dutch governmental organisations.

This Documentation Parser Service validates OpenAPI Specifications for adherence to design rules imposed by the [NL API Strategie](https://docs.geostandaarden.nl/api/API-Strategie/),
and additionally checks for correct implementation of [NLX](https://docs.nlx.io).

Getting started
-------
To run this API documentation parser docker is required.

The component contains a docker-compose file that will run the containers needed for this component. For further information on running this component, see the [Tutorial](TUTORIAL.md).

Usage
-------
Once running, OpenAPI specifications are send to `/api_docs/parser` with a POST request containing a content-type header corresponding to your OpenAPI specification
and the OpenAPI specification as its body.

Features
-------
The full feature list can be found in our [Design Decisions](DESIGN.md). Additionally our [Roadmap](ROADMAP.md) contains a list of features that are already planned for future iterations.

Requesting features
-------
Do you need a feature that is not listed? don't hesitate to send us a [feature request](https://github.com/ConductionNL/commonground-component/issues/new?assignees=&labels=&template=feature_request.md&title=).  


  

Contributing
----
First of al please read the [Contributing](CONTRIBUTING.md) guideline's ;)

But most imporantly, welcome! We strife to keep an active community at [commonground.nl](https://commonground.nl/), please drop by and tell is what you are thinking about so that we can help you along.


Credits
----

[![Conduction](https://raw.githubusercontent.com/ConductionNL/orderscomponent/master/resources/logo-conduction.svg?sanitize=true "Conduction")](https://www.conduction.nl/)





Copyright © [Conduction](https://www.conduction.nl/) 2019
