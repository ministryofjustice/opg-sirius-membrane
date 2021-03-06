# OPG Sirius Membrane *(aka Membrane)*

**Note: End-points are subject to change. Please `watch` this page.**

## Overview

* Authentication example *(with request/response body)*
* Ddc ingestion example *(with request/response header & body)*

### Installing packages locally

```shell
docker-compose run composer
```

## Authentication

### End points

##### Request `[POST] /auth/sessions`

> Curl example
>
> ```
> curl -i -X POST -H "Content-Type:application/json" -d '{"user":{"email":"YOUR@EMAIL.COM", "password":"YOUR-PASSWORD"}}' http://snapmembrane.opgcore.org.uk/auth/sessions
> ```
> Curl example with real data (false password)
>
> ```
> curl -i -X POST -H "Content-Type:application/json" -d '{"user":{"email":"scanning.team@opgtest.com", "password":"madeuppassword"}}' http://reviewmembrane.opgcore.org.uk/auth/sessions
> ```

###### Header

```
	Content-Type: application/json
```

###### Body
```JSON
	{
		"user": {
			"email": "YOUR@EMAIL.COM",
			"password": "YOUR-PASSWORD"
		}
	 }
```

##### Response `200`
```JSON
	{
		"email": "YOUR@EMAIL.COM",
		"authentication_token": "YOUR-TOKEN"
	}
```

##### Response `401`
```JSON
	{
		"error": "Invalid email or password."
	}
```

##### Response `403`
```JSON
	{
		"error": "…"
	}
```

- - -

## Backend


### End points


##### Request `[POST] /api/ddc`

> Curl example
>
> ```
> curl -i -X POST -H "HTTP-SECURE-TOKEN:YOUR-TOKEN" -H "Content-Type:text/xml" --data-binary @PATH/TO/FILE http://snapmembrane.opgcore.org.uk/api/ddc
> ```

> Curl example with real data (expired token)
>
> ```
> curl -i -X POST -H "HTTP-SECURE-TOKEN:s2G6FosttxYP_Wwv7LTb" -H "Content-Type:text/xml" --data-binary @/Users/martinsmith/Desktop/Company/Transform/OPG/Github/opg-core-ddc-examples/valid/SET_004_200_LPA002_LPA117_09-0000001-20140415122212x1.xml http://membrane.opgcore.org.uk/api/ddc
> ```



###### Header
```
	Content-Type: application/xml
	HTTP-SECURE-TOKEN: {authentication_token}
```


###### Body
```XML
	<?xml version="1.0" encoding="UTF-8"?>
<!--Sample XML file generated by XMLSpy v2014 sp1 (x64) (http://www.altova.com)-->
<LPA115 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="LPA115.xsd">
	<Page1>
		<PartA1>
			<AdditionalPersonDetails>String</AdditionalPersonDetails>
			<FullName>String</FullName>
			<Signature>true</Signature>
			<Date>String</Date>
			<ContinuationSheetNo>0</ContinuationSheetNo>
			<TotalSheets>0</TotalSheets>
		</PartA1>
		<BURN>String</BURN>
		<PhysicalPage>0</PhysicalPage>
	</Page1>
	<Page2>
		<PartA2>
			<AdditionalPersonDetails>String</AdditionalPersonDetails>
			<FullName>String</FullName>
			<Signature>true</Signature>
			<Date>String</Date>
			<ContinuationSheetNo>0</ContinuationSheetNo>
			<TotalSheets>0</TotalSheets>
		</PartA2>
		<BURN>String</BURN>
		<PhysicalPage>0</PhysicalPage>
	</Page2>
	<Page3>
		<PartA>
			<FullName>String</FullName>
			<OptionA>
				<Signature>true</Signature>
				<Date>String</Date>
			</OptionA>
			<OptionB>
				<Signature>true</Signature>
				<Date>String</Date>
			</OptionB>
		</PartA>
		<Signature>true</Signature>
		<Date>String</Date>
		<ContinuationSheetNo>0</ContinuationSheetNo>
		<TotalSheets>String</TotalSheets>
		<BURN>String</BURN>
		<PhysicalPage>0</PhysicalPage>
	</Page3>
	<Page4>
		<A3HW>
			<Witness1>
				<Signature>true</Signature>
				<Date>String</Date>
				<FullName>String</FullName>
				<Address>
					<Address1>String</Address1>
					<Address2>String</Address2>
					<Address3>String</Address3>
					<Address4>String</Address4>
					<Postcode>String</Postcode>
				</Address>
			</Witness1>
			<Witness2>
				<Signature>true</Signature>
				<Date>String</Date>
				<FullName>String</FullName>
				<Address>
					<Address1>String</Address1>
					<Address2>String</Address2>
					<Address3>String</Address3>
					<Address4>String</Address4>
					<Postcode>String</Postcode>
				</Address>
			</Witness2>
			<FullName>String</FullName>
			<ContinuationSheetNo>0</ContinuationSheetNo>
			<TotalSheets>String</TotalSheets>
		</A3HW>
		<BURN>String</BURN>
		<PhysicalPage>0</PhysicalPage>
	</Page4>
	<Page5>
		<PartB>
			<PersonalKnowledge>String</PersonalKnowledge>
			<RelevantSkills>String</RelevantSkills>
			<ContinuationSheetNo>0</ContinuationSheetNo>
			<TotalSheets>0</TotalSheets>
		</PartB>
		<BURN>String</BURN>
		<PhysicalPage>0</PhysicalPage>
	</Page5>
	<Page6>
		<PartB>
			<Salutation>
				<Mr>true</Mr>
				<Mrs>true</Mrs>
				<Ms>true</Ms>
				<Miss>true</Miss>
				<Other>true</Other>
				<OtherName>String</OtherName>
			</Salutation>
			<FirstPerson>
				<Signature>true</Signature>
				<FullName>String</FullName>
				<Date>String</Date>
			</FirstPerson>
			<LastName>String</LastName>
			<Address>
				<Address1>String</Address1>
				<Address2>String</Address2>
				<Address3>String</Address3>
				<Address4>String</Address4>
				<Postcode>String</Postcode>
			</Address>
			<Signature>true</Signature>
			<Date>String</Date>
			<ContinuationSheetNo>0</ContinuationSheetNo>
			<TotalSheets>0</TotalSheets>
		</PartB>
		<BURN>String</BURN>
		<PhysicalPage>0</PhysicalPage>
	</Page6>
</LPA115>

```

*Note: `request` property will probably be removed in the next iteration.*

##### Response `200`
```JSON
	{
		"data":{
		"success":true,
		"request":"\u003C?xml version=\u00221.0\u0022 enc ... Correspondence\u003E"
	},
	"additionalData":null
}
```

##### Response `400`
```JSON
	{
		"data":{
		"success":false,
		"request":"\u003C?xml version=\u00221.0\u0022 enc ... Correspondence\u003E"
	},
	"additionalData":null
}


##### Health-Check endpoint

hit the /auth/health-check endpoint and if it returns 200 OK then it proves that the web server, php and zend framework
are working. Note: this also calls the /api/health-check on the back-end and proves that is working too since the
back end is not accessible directly.
