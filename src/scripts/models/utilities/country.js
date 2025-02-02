/******************************************************************************\
|                                                                              |
|                                     country.js                               |
|                                                                              |
|******************************************************************************|
|                                                                              |
|        This defines an instance of model of a country.                       |
|                                                                              |
|******************************************************************************|
|     Copyright (C) 2024, Data Science Institute, University of Wisconsin      |
\******************************************************************************/

import BaseModel from '../base-model.js';

export default BaseModel.extend({

	//
	// Backbone attributes
	//

	idAttribute: 'country_id',
	urlRoot: config.servers.community_map + '/countries'
});